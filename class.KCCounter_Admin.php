<?php


class KCCounter_Admin extends KCCounter {

	function __construct(){
		parent::__construct();

		// no access
		if( ! $this->admin_access )
			return;

		require KCC_PATH . 'admin/admin_functions.php';

		add_action('admin_menu',        array(&$this, 'admin_menu') );

		add_action('delete_attachment', array(&$this, 'delete_link_by_attach_id') );
		add_action('edit_attachment',   array(&$this, 'update_link_with_attach') );

		add_filter('plugin_action_links_'. plugin_basename(__FILE__), array(&$this, 'plugins_page_links') );

		// MCE
		include KCC_PATH . 'admin/mce/mce.php';
	}

	## Ссылки на страницы статистики и настроек со страницы плагинов
	function plugins_page_links( $actions ){
		$actions[] = '<a href="admin.php?page='. KCC_NAME .'&options">'. __('Settings', 'kama-clic-counter') .'</a>';
		$actions[] = '<a href="admin.php?page='. KCC_NAME  .'">'. __('Statistics', 'kama-clic-counter') .'</a>';
		return $actions;
	}

	function admin_menu(){
		if( ! $this->admin_access ) return; // just in case...

		// открываем для всех, сюда не должно доходить, если нет доступа!....
		$hookname = add_options_page('Kama Click Counter', 'Kama Click Counter', 'read', KCC_NAME, array( & $this, 'options_page_output' ));
		add_action('load-'. $hookname, array( & $this, 'options_page_load' ) );
	}

	function options_page_load(){
		if( ! $this->admin_access ) return; // just in case...

		$this->upgrade();

		$_nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';

		if( isset($_POST['save_options']) ){
			if( ! wp_verify_nonce($_nonce, 'save_options') && check_admin_referer('save_options') ) return $this->msg = 'error: nonce failed';


			$_POST = wp_unslash( $_POST );

			// очистка
			$opt = $this->get_def_options();
			foreach( $opt as $key => & $val ){
				$val = isset($_POST[$key]) ? $_POST[$key] : '';

				if( is_string($val) ) $val = trim($val);

				if( $key === 'download_tpl' )
					$val = $val; // no sanitize... wp_kses($val, 'post');
				else
					$val = sanitize_key($val);
			}

			update_option( self::OPT_NAME, $opt );

			if( $this->opt = get_option( self::OPT_NAME ) )
				$this->msg = __('Settings updated.', 'kama-clic-counter');
			else
				$this->msg = __('Error: Failed to update the settings!', 'kama-clic-counter');
		}
		elseif( isset($_POST['reset']) ){
			if( ! wp_verify_nonce($_nonce, 'save_options') && check_admin_referer('save_options') ) return $this->msg = 'error: nonce failed';

			$this->set_def_options();
			$this->msg = __('Settings reseted to defaults', 'kama-clic-counter');
		}
		// update_link
		elseif( isset($_POST['update_link']) ){
			if( ! wp_verify_nonce($_nonce, 'update_link') && check_admin_referer('update_link') ) return $this->msg = 'error: nonce failed';

			$data = wp_unslash( $_POST['up'] );
			$id   = (int) $data['link_id'];
			//unset( $data['update_link'], $data['local_referer'] );

			$this->msg = $this->update_link( $id, $data ) ? __('Link updated!', 'kama-clic-counter') : 'error: ' . __('Failed to update link!', 'kama-clic-counter');
		}
		elseif( isset($_POST['delete_link_id']) ){
			if( ! wp_verify_nonce($_nonce, 'bulk_action') && check_admin_referer('bulk_action') ) return $this->msg = 'error: nonce failed';

			if( $this->delete_links($_POST['delete_link_id']) )
				$this->msg = __('Selected objects deleted', 'kama-clic-counter');
			else
				$this->msg = __('Nothing was deleted!', 'kama-clic-counter');
		}
		// delete single link handler
		elseif( isset($_GET['delete_link']) ){
			if( ! wp_verify_nonce($_nonce, 'delete_link') ) return $this->msg = 'error: nonce failed';

			if( $this->delete_links($_GET['delete_link']) )
				wp_redirect( remove_query_arg( array( 'delete_link', '_wpnonce') ) );
			else
				$this->msg = __('Nothing was deleted!', 'kama-clic-counter');
		}
	}

	function admin_page_url(){
		return admin_url('admin.php?page='. KCC_NAME );
	}

	function options_page_output(){
		include KCC_PATH . 'admin/admin.php';
	}

	function set_def_options(){
		update_option( self::OPT_NAME, $this->get_def_options() );

		return $this->opt = get_option( self::OPT_NAME );
	}

	function get_def_options(){
		$array = array(
			'download_tpl' => '
				<div class="kcc_block" title="Скачать" onclick="document.location.href=\'[link_url]\'">
					<img class="alignleft" src="[icon_url]" alt="" />
					<a class="kcc-link" href="[link_url]" title="[link_name]">Скачать: [link_title]</a>
					<div class="description">[link_description]</div>
					<small>Скачано: [link_clicks], размер: [file_size], дата: [link_date:d.M.Y]</small>
					<b><!-- clear --></b>
					[edit_link]
				</div>

				<style type="text/css">
					.kcc_block{ position:relative; padding:15px 10px; margin-bottom:20px; cursor:pointer; transition:background-color 0.4s; }
					.kcc_block:before{ clear:both; }
					.kcc_block a{ border-bottom:0; }
					.kcc_block a.kcc-link{ text-decoration:none; display:block; font-size:150%; line-height:1.2; }
					.kcc_block img{ width:55px; height:auto; float:left; margin:0 25px 0 5px;border:0px!important; box-shadow:none!important; }
					.kcc_block .description{ color:#666; }
					.kcc_block small{ color:#ccc; }
					.kcc_block b{ display:block; clear:both; }
					.kcc_block:hover{ outline:2px solid rgba(125, 125, 125, 0.18); }
					.kcc_block:hover a{ text-decoration:none; }
					.kcc_block .kcc-edit-link{ position:absolute; top:0; right:0.2em; }
				</style>',
			'links_class'  => 'count', // проверять class в простых ссылках
			'add_hits'     => '',         // may be: '', 'in_title' or 'in_plain' (for simple links)
			'in_post'      => 1,
			'hide_url'     => false,      // прятать ссылку или нет?
			'widget'       => 1,          // включить виджет для WordPress
			'toolbar_item' => 1,          // выводить ссылку на статистику в Админ баре?
			'access_roles' => array(),    // Название ролей, кроме администратора, которым доступно упраление плагином.
		);

		$array['download_tpl'] = trim( preg_replace('~^\t{4}~m', '', $array['download_tpl']) );

		return $array;
	}

	function update_link( $id, $data ){
		global $wpdb;

		if( $id = (int) $id )
			$query = $wpdb->update( $wpdb->kcc_clicks, $data, array('link_id' => $id) );

		// обновление вложения, если оно есть
		if( $data['attach_id'] > 0 ){
			$wpdb->update( $wpdb->posts,
				array('post_title' => $data['link_title'], 'post_content' => $data['link_description']),
				array('ID' => $data['attach_id'])
			);
		}

		return $query;
	}

	function delete_link_url( $link_id ){
		return add_query_arg( array( 'delete_link'=>$link_id, '_wpnonce'=>wp_create_nonce('delete_link') ) );
	}

	/**
	 * Удаление ссылок из БД по переданному массиву ID или ID ссылки
	 * @param  array/int [$array_ids = array()] ID ссылок котоыре нужно удалить
	 * @return boolean  Удалено ли
	 */
	function delete_links( $array_ids = array() ){
		global $wpdb;

		$array_ids = array_filter( array_map('intval', (array) $array_ids ) );

		if( ! $array_ids )
			return false;

		return $wpdb->query( "DELETE FROM $wpdb->kcc_clicks WHERE link_id IN (". implode(',', $array_ids) .")" );
	}

	## Удаление ссылки по ID вложения
	function delete_link_by_attach_id( $attach_id ){
		global $wpdb;

		if( ! $attach_id )
			return false;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->kcc_clicks WHERE attach_id = %d", $attach_id ) );
	}

	## Обновление ссылки, если обновляется вложение
	function update_link_with_attach( $attach_id ){
		global $wpdb;

		$attdata = wp_get_single_post( $attach_id );

		$new_data = wp_unslash( array(
			'link_description' => $attdata->post_content,
			'link_title' => $attdata->post_title,
			'link_date' => $attdata->post_date,
		) );

		return $wpdb->update( $wpdb->kcc_clicks, $new_data, array( 'attach_id' => $attach_id ) );
	}

	function activation(){
		global $wpdb;

		$charset_collate  = (! empty( $wpdb->charset )) ? "DEFAULT CHARSET=$wpdb->charset" : '';
		$charset_collate .= (! empty( $wpdb->collate )) ? " COLLATE $wpdb->collate" : '';

		// Создаем таблицу если такой еще не существует
		$sql = "CREATE TABLE $wpdb->kcc_clicks (
			link_id           bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			attach_id         bigint(20) UNSIGNED NOT NULL default 0,
			in_post           bigint(20) UNSIGNED NOT NULL default 0,
			link_clicks       bigint(20) UNSIGNED NOT NULL default 1,
			link_name         varchar(191)        NOT NULL default '',
			link_title        text                NOT NULL ,
			link_description  text                NOT NULL ,
			link_date         date                NOT NULL default '1970-01-01',
			last_click_date   date                NOT NULL default '1970-01-01',
			link_url          text                NOT NULL ,
			file_size         varchar(100)        NOT NULL default '',
			downloads         ENUM('','yes')      NOT NULL default '',
			PRIMARY KEY  (link_id),
			KEY in_post (in_post),
			KEY downloads (downloads),
			KEY link_url (link_url(191))
		) $charset_collate";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		//wp_die();

		$this->upgrade();

		if( ! get_option( self::OPT_NAME ) )
			$this->set_def_options();
	}

	function upgrade(){
		$prev_ver = get_option('kcc_version');

		if( $prev_ver === KCC_VER || ! $prev_ver ) return;

		update_option('kcc_version', KCC_VER );

		global $wpdb;

		// обнволение структуры таблиц
		//require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		//$doe = dbDelta( dem_get_db_schema() );
		//wp_die(print_r($doe));

		$fields = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->kcc_clicks");
		// название поля в индекс
		foreach( $fields as $k => $data ){
			$fields[ $data->Field ] = $data;
			unset($fields[ $k ]);
		}
		/*
		$fields = Array (
			[link_id] => stdClass Object (
				[Field] => link_id
				[Type] => bigint(20) unsigned
				[Null] => NO
				[Key] => PRI
				[Default] =>
				[Extra] => auto_increment
			)

			[link_url] => stdClass Object (
				[Field] => link_url
				[Type] => text
				[Null] => NO
				[Key] => MUL
				[Default] =>
				[Extra] =>
			)
			...
		*/

		//die( print_r($fields) );

		$charset_collate  = 'CHARACTER SET ' . ( (! empty( $wpdb->charset )) ? $wpdb->charset : 'utf8' );
		$charset_collate .= ' COLLATE ' . ( (! empty( $wpdb->collate )) ? $wpdb->collate : 'utf8_general_ci' );

		// 3.0
		if( ! isset($fields['last_click_date']) ){
			// $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content, '[download=', '[download url=')");
			// обновим таблицу

			// добавим поле: дата последнего клика
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD `last_click_date` DATE NOT NULL default '0000-00-00' AFTER link_date");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD `downloads` ENUM('','yes') NOT NULL default ''");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD INDEX  `downloads` (`downloads`)");

			// обновим существующие поля
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_date`  `link_date` DATE NOT NULL default  '0000-00-00'");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_id`    `link_id`   BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `attach_id`  `attach_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `in_post`    `in_post`   BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_clicks`  `link_clicks` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks DROP  `permissions`");
		}

		// 3.4.7
		if( $fields['link_url']->Type != 'text' ){
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_name`        `link_name`        VARCHAR(191) $charset_collate NOT NULL default ''");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_title`       `link_title`       text         $charset_collate NOT NULL ");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_url`         `link_url`         text         $charset_collate NOT NULL ");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_description` `link_description` text         $charset_collate NOT NULL ");
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `file_size`        `file_size`        VARCHAR(100) $charset_collate NOT NULL default ''");
		}

		if( $fields['link_url']->Key )
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks DROP INDEX link_url, ADD INDEX link_url (link_url(191))");
		else
			$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD INDEX link_url (link_url(191))");

	}

}
