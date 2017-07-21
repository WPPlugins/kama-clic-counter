<?php

/* included in KCC Class */

// TinyMCE
if ( ! get_user_option('rich_editing') )
	return;

add_filter('mce_buttons_2',        'kcc_mce_register_buttons');
add_filter('mce_external_plugins', 'kcc_mce_js');
add_filter('wp_mce_translation',   'kcc_tinymce_l10n');

function kcc_mce_register_buttons( $buttons ){
	$last = array_pop( $buttons );
	$buttons[] = "kcc";
	$buttons[] = $last;
	return $buttons;
}

function kcc_mce_js( $plugin_array ){
	$plugin_array['KCC'] = KCC_URL . 'admin/mce/mce.js';
	return $plugin_array;
}

function kcc_tinymce_l10n( $mce_l10n ){
	$l10n = array_map('esc_js', array(
		'kcc frame button title'   => __('Select file', 'kama-clic-counter'),
		'kcc find url frame title' => __('Find file for download shortcode', 'kama-clic-counter'),
		'kcc modal title' => __('Click counter shortcode insertion', 'kama-clic-counter'),
		'kcc button text' => __('Insert shortcode', 'kama-clic-counter'),
		'kcc input title' => __('Link title (not required)', 'kama-clic-counter'),
		'kcc input link'  => __('Download file URL', 'kama-clic-counter'),
	) );

	return $mce_l10n + $l10n;
}


