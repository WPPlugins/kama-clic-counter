<?php

class KCCounter {

	const OPT_NAME  = 'kcc_options';
	const COUNT_KEY = 'kcccount';
	const PID_KEY   = 'kccpid';

	protected $admin_access;

	public $opt;

	static $inst;

	static function instance(){
		if( is_null( self::$inst ) ) self::$inst = is_admin() ? new KCCounter_Admin() : new self;

		return self::$inst;
	}

	function __construct(){
		if( ! is_null( self::$inst ) ) return self::$inst;

		global $wpdb;

		$this->opt = get_option( self::OPT_NAME );

		// access
		// set it here in order to use in front
		$this->admin_access = apply_filters('kcc_admin_access', null );
		if( $this->admin_access === null ){
			$this->admin_access = current_user_can('manage_options');

			if( ! $this->admin_access && ! empty($this->opt['access_roles']) ){
				foreach( wp_get_current_user()->roles as $role ){
					if( in_array($role, $this->opt['access_roles'] ) ){
						$this->admin_access = true;
						break;
					}
				}
			}
		}

		// set table name
		$wpdb->tables[]   = 'kcc_clicks';
		$wpdb->kcc_clicks = $wpdb->prefix . 'kcc_clicks';

		// локализация
		//if( ($locale = get_locale()) && (substr($locale,0,3) !== 'en_') ) $res = load_textdomain('kcc', dirname(__FILE__) . '/lang/'. $locale . '.mo' );

		// Рабочая часть
		if( $this->opt['links_class'] )
			add_filter('the_content', array(&$this, 'modify_links') );

		// admin_bar
		if( $this->opt['toolbar_item'] && $this->admin_access )
			add_action('admin_bar_menu', array( &$this, 'add_toolbar_menu'), 90 );

		add_action('wp_footer', array( &$this, 'add_script_to_footer'), 99);
		add_action('wp_footer', array( &$this, 'enqueue_jquery_if_need'), 0);

		//add_action('wp_enqueue_scripts', create_function('', 'wp_enqueue_script("jquery");'), 0 ); // early jquery enqueue, in order it could be changed

		// добавляем шоткод загрузок
		add_shortcode('download', array(&$this, 'download_shortcode') );

		// событие редиректа
		add_filter('init', array(&$this, 'redirect'), 0);

		// Добавляем Виджет
		if( $this->opt['widget'] )
			require_once KCC_PATH . 'widget.php';
	}

	function enqueue_jquery_if_need(){
		if( ! wp_script_is( 'jquery', 'enqueued' ) )
			wp_enqueue_script('jquery');
	}

	## jQuery добавка для подсчета ссылок на всем сайте
	function add_script_to_footer(){
		$kcc_url_patt = $this->get_kcc_url( '{url}', '{in_post}', '{download}' );

		ob_start();
		?>
		<!-- KCCounter -->
		<script type="text/javascript">
			try {
				var kcckey  = '<?php echo self::COUNT_KEY ?>',
					pidkey  = '<?php echo self::PID_KEY ?>',
					urlpatt = '<?php echo $kcc_url_patt ?>',
					onclickEvents = 'click contextmenu mousedown',
					kccclickFunc = function(e){
						this.href = e.data.kccurl;
					};

				// add kcc url to 'count' links
				jQuery('a.<?php echo $this->opt['links_class'] ?>').each(function(){
					var $a   = jQuery(this),
						href = $a.attr('href'), // original
						pid  = $a.data( pidkey ),
						kccurl;

					if( href.indexOf(kcckey) !== -1 ) return; // only for not modified links

					kccurl = urlpatt.replace('{in_post}', (pid ? pid : '') );
					kccurl = kccurl.replace('{download}', ( !! $a.data('kccdownload') ? 1 : '') );
					kccurl = kccurl.replace('{url}', href );

					$a.attr('data-kcc', 1).on(onclickEvents, { kccurl: kccurl }, kccclickFunc );
				});

				// hide ugly kcc url
				jQuery('a[href*="'+ kcckey +'"]').each(function(){
					var $a   = jQuery(this),
						href = $a.attr('href'), // original
						re   = new RegExp( kcckey +'=(.*)' ),
						url;

					if( url = href.match(re)[1] ){
						if( !! parseInt(url) ) url = '/#download'+ url;
						$a.attr('data-kcc', 1).attr('href', url ).on(onclickEvents, { kccurl: href }, kccclickFunc );
					}

				});
			} catch(e){}
		</script>
		<?php
		$scr = ob_get_clean();
		$scr = preg_replace('~[^:]//[^\n]+|[\t\n\r]~', '', $scr ); // remove: comments, \t\r\n
		$scr = preg_replace('~[ ]{2,}~', ' ', $scr );
		echo $scr ."\n";
	}

	## получает ссылку по которой будут считаться клики
	function get_kcc_url( $url = '', $in_post = 0, $download = 0 ){
		// порядок имеет значение...
		$vars = array(
			'download'      => $download,
			self::PID_KEY   => $in_post,
			self::COUNT_KEY => $url,
		);

		if( ! $this->opt['in_post'] ) unset( $vars[ self::PID_KEY ] );

		$kcc_url = array();
		foreach( $vars as $key => $val ){
			if( $val ) $kcc_url[] = $key .'='. trim($val);
		}

		$kcc_url = home_url() .'?'.  implode('&', $kcc_url );

		if( $this->opt['hide_url'] ){
			$kcc_url = $this->hide_link_url( $kcc_url );
		}

		return apply_filters('get_kcc_url', $kcc_url );
	}

	/**
	 * Прячет оригинальную ссылку под ID ссылки. Ссылка должна существовать в БД.
	 * @param  string $kcc_url URL плагина для подсчета ссылки.
	 * @return string URL со спрятанной ссылкой.
	 */
	function hide_link_url( $kcc_url ){
		$parsed = $this->parce_kcc_url( $kcc_url );

		// не прячем если это простая ссылка или урл уже спрятан
		if( ! @ $parsed['download'] || is_numeric( @ $parsed[ KCCounter::COUNT_KEY ]) )
			return $kcc_url;

		// не прячем если ссылки нет в БД
		if( ! $link = $this->get_link($kcc_url) )
			return $kcc_url;

		return str_replace( $parsed[ KCCounter::COUNT_KEY ], $link->link_id, $kcc_url );
	}

	#### counting part --------------------------------------
	## add clicks by given url
	function do_count( $kcc_url, $count = true ){
		global $wpdb;

		$parsed = $this->parce_kcc_url( $kcc_url );

		$args = array(
			'link_url'  => $parsed[ self::COUNT_KEY ],
			'in_post'   => (int) $parsed[ self::PID_KEY ],
			'downloads' => @ $parsed['download'] ? 'yes' : '',
			'kcc_url'   => $kcc_url,
			'count'     => $count,
		);

		$link_url = & $args['link_url'];
		$curr_time = current_time('mysql');

		if( is_numeric($link_url) )
			$WHERE = 'link_id = '. $link_url;
		else{
			$AND = '';
			if( $this->opt['in_post'] ) $AND  = ' AND in_post = '. (int) $args['in_post'];
			if( $args['downloads'] )    $AND .= $wpdb->prepare(' AND downloads = %s', $args['downloads'] );

			$WHERE = $wpdb->prepare('link_url = %s ', $link_url ) . $AND;
		}

		$sql = "UPDATE $wpdb->kcc_clicks SET link_clicks = (link_clicks + 1), last_click_date = '". $curr_time ."' WHERE $WHERE LIMIT 1";
		//$wpdb->prepare(); // юзать не катит потому возвращается false, если передатется ссылка с закодированной кириллицей, почему-то в ней используются % - /%d0%bf%d1%80%d0%b8%d0%b2%d0%b5%d1%82...

		// костыль: обновим дубли... Возможно надо сделать блокировку таблицы при записи...
		if( $more_links = $wpdb->get_results("SELECT * FROM $wpdb->kcc_clicks WHERE $WHERE LIMIT 1,999") ){
			$up_link_id = $wpdb->get_var("SELECT link_id FROM $wpdb->kcc_clicks WHERE $WHERE LIMIT 1");
			foreach( $more_links as $link ){
				$wpdb->query("UPDATE $wpdb->kcc_clicks SET link_clicks = (link_clicks + 1) WHERE link_id = $up_link_id;");
				$wpdb->query("DELETE FROM $wpdb->kcc_clicks WHERE link_id = $link->link_id;");
			}
		}


		$data = array(); // данные добавляемой в БД ссылки

		do_action_ref_array('kcc_count_before', array($args, & $sql, & $data) );

		// пробуем обновить данные
		$updated = $wpdb->query( $sql );

		// счетчик обновлен
		if( $updated ){
			$return = true;
		}
		// добавляем данные
		else{
			// добавляем данные в бд...
			$data = array_merge( array(
				'attach_id'        => 0,
				'in_post'          => $args['in_post'],
				'link_clicks'      => $args['count'] ? 1 : 0, // Для загрузок, когда запись добавляется просто при просмотре, все равно добавляется 1 первый просмотр, чтобы добавить запись в бД
				'link_name'        => untrailingslashit( $this->is_file($link_url) ? basename($link_url) : preg_replace('~(https?|ftp)://~', '', $link_url ) ),
				'link_title'       => '', // устанавливается отдлеьно ниже
				'link_description' => '',
				'link_date'        => $curr_time,
				'last_click_date'  => $curr_time,
				'link_url'         => $link_url,
				'file_size'        => self::file_size( $link_url ),
				'downloads'        => $args['downloads'],
			), $data );

			// если кирилический домен
			if( false !== stripos( $data['link_name'], 'xn--') ){
				$host = parse_url( $data['link_url'], PHP_URL_HOST );
				//die( var_dump($host) );

				require_once KCC_PATH .'php-punycode/idna_convert.class.php';
				$ind = new idna_convert();

				$data['link_name'] = str_replace( $host, $ind->decode($host), $data['link_name'] );
			}
			//die( print_r($data) );

			$title = & $data['link_title']; // easy life

			// is_attach?
			if( $attach = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s", $link_url ) ) ){
				$title                    = $attach->post_title;
				$data['attach_id']        = $attach->ID;
				$data['link_description'] = $attach->post_content;
			}

			// get link_title from url
			if( ! $title ){
				if( $this->is_file($link_url) ){
					$title = preg_replace('~[.][^.]+$~', '', $data['link_name'] ); // delete ext
					$title = preg_replace('~[_-]~', ' ', $title);
					$title = ucwords( $title );
				}
				else{
					$title = $this->get_html_title( $link_url );
				}
			}
			// если так и не удалось определить
			if( ! $title )
				$title = $data['link_name'];

			$data = apply_filters('kcc_insert_link_data', $data, $args );

			//$wpdb->query("LOCK TABLES $wpdb->kcc_clicks");
			$return = $wpdb->insert( $wpdb->kcc_clicks, $data ) ? $wpdb->insert_id : false;
			//$wpdb->query("UNLOCK TABLES $wpdb->kcc_clicks");
		}

		do_action('kcc_count_after', $args, $updated, $data );

		$this->clear_link_cache( $kcc_url ); // чистим кэш...

		return $return;
	}


	/**
	 * Разибирает KСС УРЛ.
	 * Конвертирует относительный путь "/blog/dir/file" в абсолютный (от корня сайта) и чистит УРЛ
	 * РАсчитан на прием грязных/неочищенных URL.
	 *
	 * @param  string $kcc_url Kcc УРЛ.
	 * @return array параметры переданой строки
	 */
	function parce_kcc_url( $kcc_url ){
		preg_match('~\?(.+)$~', $kcc_url, $m ); // get kcc url query args
		$kcc_query = $m[1]; // parse_url( $kcc_url, PHP_URL_QUERY );

		// cut URL from $query, because - there could be query args (&) that is why cut it
		$split = preg_split( '~&?'. self::COUNT_KEY .'=~', $kcc_query );
		$query = & $split[0];
		$url   = & $split[1];

		if( ! $url )
			return array();

		// parse other query part
		parse_str( $query, $query_args );

		$url = preg_replace('/#.*$/', '', $url ); // delete #anchor

		if( $url{0} == '/' )
			$url = rtrim( home_url(), '/' ) . $url;

		return array(
			self::COUNT_KEY => $url, // no esc_url()
			self::PID_KEY   => (int) @ $query_args[ self::PID_KEY ],
			'download'      => !! @ $query_args['download'], //array_key_exists('download', $query_args ), // isset null не берет
		);

		$the_cache = apply_filters('parce_kcc_url', $the_cache );

		return $the_cache;
	}

	function is_file( $url ){
		// replace method work
		$return = apply_filters('kcc_is_file', null );
		if( null !== $return )
			return $return;

		if( ! preg_match('~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url, $m ) )
			return false;

		$f_ext = $m[1];

		$not_supported_ext = array('html', 'htm', 'xhtml', 'xht', 'php');

		if( in_array( $f_ext, $not_supported_ext ) )
			return false;

		return true; // any other ext - is true
	}

	/**
	 * return title of a (local or remote) webpage
	 * @param  string $url URL title we get to
	 * @return string   title
	 */
	function get_html_title( $url ){
		$file = @ file_get_contents( $url, false, null, 0, 10000 );
		if( $file && preg_match('@<title>(.*)</title>@is', $file, $m ) )
			return $m[1];

		return '';
	}

	## Получает размер файла по сылке
	static function file_size( $url ){
		//$url = urlencode( $url );

		$size = null;

		// direct. considers WP subfolder install
		if( ! $size && (false !== strpos( $url, home_url() )) ) {
			$path_part = str_replace( home_url(), '', $url );
			$file = wp_normalize_path( ABSPATH . $path_part );
			// если вп во вложенной папке...
			if( ! file_exists( $file ) )
				$file = wp_normalize_path( dirname(ABSPATH) . $path_part );
			$size = @ filesize( $file );
		}
		// curl enabled
		if( ! $size && function_exists('curl_version') ){
			$size = self::curl_get_file_size( $url );
		}
		// get_headers
		if( ! $size && function_exists('get_headers') ){
			$headers = @ get_headers( $url, 1 );
			$size = @ $headers['Content-Length'];
		}

		$size = (int) $size;

		if( ! $size )
			return 0;

		$i = 0;
		$type = array("B", "KB", "MB", "GB");
		while( ( $size/1024 ) > 1 ){
			$size = $size/1024;
			$i++;
		}
		return substr( $size, 0, strpos($size,'.')+2 ) .' '. $type[ $i ];
	}

	/**
	 * Returns the size of a file without downloading it.
	 *
	 * @param $url - The location of the remote file to download. Cannot
	 * be null or empty.
	 *
	 * @return The size of the file referenced by $url, or false if the size
	 * could not be determined.
	 */
	static function curl_get_file_size( $url ) {
		$curl = curl_init( $url );

		// Issue a HEAD request and follow any redirects.
		curl_setopt( $curl, CURLOPT_NOBODY, true );
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );

		$data = curl_exec( $curl );
		curl_close( $curl );

		$result = false; // Assume failure.

		if( ! $data )
		  return $result;

		$content_length = "unknown";
		$status = "unknown";

		if( preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches ) )
		  $status = (int) $matches[1];

		if( preg_match("/Content-Length: (\d+)/", $data, $matches ) )
		  $content_length = (int)$matches[1];

		// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		if( $status == 200 || ($status > 300 && $status <= 308) )
			$result = $content_length;

		return $result;
	}

	#### text replacement part --------------------------------------
	## change links that have special class in given content
	function modify_links( $content ){
		if( false === strpos( $content, $this->opt['links_class'] ) )
			return $content;

		return preg_replace_callback("@<a ([^>]*class=['\"][^>]*{$this->opt['links_class']}(?=[\s'\"])[^>]*)>(.+?)</a>@", array( & $this, 'do_simple_link' ), $content );
	}

	## parses string to detect and process pairs of tag="value"
	function do_simple_link( $match ){
		global $post;

		$link_attrs  = $match[1];
		$link_anchor = $match[2];
		preg_match_all('~[^=]+=([\'"])[^\1]+?\1~', $link_attrs, $args );
		foreach( $args[0] as $pair ){
			list($tag, $value) = explode('=', $pair, 2);
			$value = trim( trim($value, '"\'') );
			$args[ trim($tag) ] = $value;
		}
		unset($args[0]);
		unset($args[1]);

		$args['data-'. self::PID_KEY ] = $post->ID;
		if( $this->opt['add_hits'] ){
			$link = $this->get_link( $args['href'] );

			if( $link && $link->link_clicks ){
				if ( $this->opt['add_hits'] == 'in_title' )
					$args['title'] = "(". __('clicks:', 'kama-clic-counter') ." {$link->link_clicks})". $args['title'];
				else
					$after = ($this->opt['add_hits']=='in_plain') ? ' <span class="hitcounter">('. __('clicks:', 'kama-clic-counter') .' '. $link->link_clicks .')</span>' : '';
			}
		}

		$link_attrs = '';
		foreach( $args as $key => $value )
			$link_attrs .= "$key=\"$value\" ";

		$link_attrs = trim($link_attrs);

		return '<a '. $link_attrs .'>'. $link_anchor .'</a>'. @ $after;
	}

	## получает ссылку на картинку иконки g расширению переданной ссылки
	function get_url_icon( $url ){
		$url_path = parse_url( $url, PHP_URL_PATH );

		if( preg_match('~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url_path, $m ) )
			$icon_name = $m[1] .'.png';
		else
			$icon_name = 'default.png';

		$icon_name  = file_exists( KCC_PATH . "icons/$icon_name") ? $icon_name : 'default.png';

		$icon_url = KCC_URL . "icons/$icon_name";

		return apply_filters('get_url_icon', $icon_url, $icon_name );
	}

	function download_shortcode( $atts = array() ){
		global $post;

		// белый список параметров и значения по умолчанию
		$atts = shortcode_atts( array(
			'url'   => '',
			'title' => '',
		), $atts );

		if( ! $atts['url'] ) return '[download]';

		$kcc_url = $this->get_kcc_url( $atts['url'], $post->ID, 1 );

		// записываем данные в БД
		if( ! $link = $this->get_link( $kcc_url ) ){
			$this->do_count( $kcc_url, $count = false ); // для проверки, чтобы не считать эту операцию
			$link = $this->get_link( $kcc_url );
		}

		$tpl = $this->opt['download_tpl'];
		$tpl = str_replace('[link_url]', esc_url($kcc_url), $tpl );

		if( $atts['title'] ) $tpl = str_replace('[link_title]', $atts['title'], $tpl );

		return $this->tpl_replace_shortcodes( $tpl, $link );
	}

	/**
	 * Заменяет шоткоды в шаблоне на реальные данные
	 * @param  string $tpl  Шаблон для замены в нем данных
	 * @param  object $link данные ссылки из БД
	 * @return string HTML код блока - замененный шаблон
	 */
	function tpl_replace_shortcodes( $tpl, $link ){
		$tpl = str_replace('[icon_url]', $this->get_url_icon( $link->link_url ), $tpl );
		$tpl = str_replace('[edit_link]', $this->edit_link_url( $link->link_id ), $tpl );

		if( preg_match('@\[link_date:([^\]]+)\]@', $tpl, $date) )
			$tpl = str_replace( $date[0], apply_filters('get_the_date', mysql2date($date[1], $link->link_date) ), $tpl );

		// меняем все остальные шоткоды
		preg_match_all('@\[([^\]]+)\]@', $tpl, $match );
		foreach( $match[1] as $data ){
			$tpl = str_replace("[$data]", $link->$data, $tpl );
		}

		return $tpl;
	}

	function clear_link_cache( $kcc_url ){
		$this->get_link( $kcc_url, 'clear_cache' );
	}

	/**
	 * Получает данные уже существующие ссылки из БД.
	 *
	 * Если не удалось получить ссылку кэш не устанавливается.
	 * @param  string/int $kcc_url            URL или ID ссылки, или kcc_URL
	 * @param  boolean [$clear_cache = false] Когда нужно очистить кэш ссылки.
	 * @return object/NULL   null при очистке кэша или если не удалось получить данные.
	 */
	function get_link( $kcc_url, $clear_cache = false ){
		static $cache;

		if( $clear_cache ){
			unset($cache[$kcc_url]);
			return;
		}

		if( isset($cache[$kcc_url]) )
			return $cache[$kcc_url];

		// тут кэш юзать можно только со сбросом в нужном месте...
		global $wpdb;

		// если это прямая ссылка а не kcc_url
		if( is_numeric($kcc_url) || false === strpos( $kcc_url, self::COUNT_KEY ) )
			$link_url = $kcc_url;
		else{
			$parsed = $this->parce_kcc_url( $kcc_url );

			$link_url = $parsed[ self::COUNT_KEY ];
			$pid      = $parsed[ self::PID_KEY ];
		}

		// передан ID ссылки, а не URL
		if( is_numeric($link_url) )
			$WHERE = $wpdb->prepare("link_id = %d", $link_url );
		else{
			$in_post = @ $pid ? $wpdb->prepare(' AND in_post = %d', $pid ) : '';
			$WHERE = $wpdb->prepare("link_url = %s ", $link_url ) . $in_post;
		}

		$link_data = $wpdb->get_row("SELECT * FROM $wpdb->kcc_clicks WHERE $WHERE");

		if( $link_data )
			$cache[$kcc_url] = $link_data;

		return $link_data;
	}

	## возвращает УРЛ на редактирование ссылки в админке
	function edit_link_url( $link_id, $edit_text = '' ){
		if( ! $this->admin_access ) return '';

		if( ! $edit_text ) $edit_text = '✎';

		return '<a class="kcc-edit-link" href="'. admin_url('admin.php?page='. KCC_NAME .'&edit_link='. $link_id ) .'">'. $edit_text .'</a>';
	}

	## toolbar link
	function add_toolbar_menu( $toolbar ) {
		$toolbar->add_menu( array(
			'id'    => 'kcc',
			'title' => __('KCC stat', 'kama-clic-counter'),
			'href'  => admin_url('admin.php?page='. KCC_NAME ),
		) );
	}

	### redirect ---------------------------------------------
	function redirect(){
		if( ! isset($_GET[ self::COUNT_KEY ]) )
			return;

		$url = $_GET[ self::COUNT_KEY ];

		// для переопределения функции
		if( apply_filters('kcc_redefine_redirect', false, $this ) )
			return;

		// считаем
		if( apply_filters('kcc_do_count', true, $this ) )
			$this->do_count( $_SERVER['REQUEST_URI'] );

		if( is_numeric($url) ){
			if( ! $link_data = $this->get_link( $_SERVER['REQUEST_URI'] ) )
				return;

			$url = $link_data->link_url;
		}

		// перенаправляем
		if( headers_sent() )
			print "<script>location.replace('". esc_url($url) ."');</script>";
		else{
			wp_redirect( $url, 303 );

//            global $is_IIS;
//
//            $status = 303;
//
//            if ( !$is_IIS && PHP_SAPI != 'cgi-fcgi' )
//                status_header($status); // This causes problems on IIS and some FastCGI setups
//
//            $url = wp_sanitize_redirect( $url );
//			header("Location: $url", true, $status ); // wp_redirect() не подходит...
		}

		exit;
	}

}
