<?php
/**
 * Plugin Name: Kama Click Counter
 * Description: Count clicks on any link all over the site. Creates beautiful file download block in post content - use shortcode [download url="any file URL"]. Has widget of top clicks/downloads.
 *
 * Text Domain: kama-clic-counter
 * Domain Path: /languages
 *
 * Author: Kama
 * Author URI: https://wp-kama.ru
 * Plugin URI: https://wp-kama.ru/?p=430
 *
 * Version: 3.6.1
 *
 * Build: 119
 */

if( ! defined('ABSPATH') ) exit; // no direct access

if( defined('WP_INSTALLING') && WP_INSTALLING ) return;

$data = get_file_data( __FILE__, array('Version'=>'Version', 'Domain Path'=>'Domain Path') );

define('KCC_VER',  $data['Version'] );

define('KCC_PATH', plugin_dir_path(__FILE__) );
define('KCC_URL',  plugin_dir_url(__FILE__) );
define('KCC_NAME', basename(KCC_PATH) ); // folder name - kama-click-counter

require_once KCC_PATH . 'class.KCCounter.php';
require_once KCC_PATH . 'class.KCCounter_Admin.php';
require_once KCC_PATH . '_backward_compatibility.php';

register_activation_hook( __FILE__, create_function('', 'KCCounter()->activation();') );

add_action('plugins_loaded', 'KCCounter_init' );
function KCCounter_init(){
	load_plugin_textdomain( 'kama-clic-counter', false, KCC_NAME .'/languages' );

	KCCounter();
}

// gets instance
function KCCounter(){
	return KCCounter::instance();
}



