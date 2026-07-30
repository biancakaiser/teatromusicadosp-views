<?php
/*
Plugin Name: Teatro Extra View Modes
Plugin URI: https://wp.teatromusicadosp.com.br/
Description: Adds extra viewmodes to the Teatro project, using the Tainacan plugin as a base.
Author: Teatro Musicado SP
Version: 1.0.0
Text Domain: teatro-extra-viewmodes
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/** Plugin version */
const TEATRO_EXTRA_VIEWMODES_PLUGIN_VERSION = '0.0.1';

/**
 * View modes that are made of Vuejs components instead of php
 * templates have to be registered first on the plugin
 */
add_action("tainacan-register-vuejs-component", "teatro_extra_viewmodes_register_components");
function teatro_extra_viewmodes_register_components($helper) {

    if ( function_exists( 'tainacan_register_view_mode' ) ) {
		
		// Enqueues necessary third party or modified libraries to this view mode
		$baseurl =  plugin_dir_url(__FILE__);

        // Registering the Vue Component
        $handle = 'teatro-accordion-viewmode';
        $component_script_url = 'http://localhost:8080/accordion-view-mode.bundle.js';
		$helper->register_vuejs_component($handle, $component_script_url, [ 'public' => true, 'deps' => ['wp-i18n'] ], null, true);
		
		// Registering the view mode
        tainacan_register_view_mode('accordion-viewmode', [
            'label' 				=> 'Accordion',
			'description' 			=> __('A boring component demo view mode.', 'teatro-extra-viewmodes'),
			'icon' 					=> '<span class="icon"><i><svg fill="var(--tainacan-info-color, #555758)" xmlns="http://www.w3.org/2000/svg" height="24" width="24"><path d="M8.492 6.074h7.016v11.852H8.492zM4.943 7.477h2.806v9.046H4.943zM16.251 7.477h2.807v9.046H16.25zM19.8 8.442h1.884v7.116h-1.883zM2.316 8.442h1.883v7.116H2.316z"/></svg></i></span>',
            'type' 					=> 'component',
			'component' 			=> 'view-mode-accordion',
			'dynamic_metadata' 		=> true,
			'implements_skeleton' 	=> true
        ]);
    }
}

/**
 * Template view modes have their style separated from the php file
 * so we enqueue them here.
 */
add_action( 'wp_print_scripts', 'teatro_extra_viewmodes_enqueue_styles' );
function teatro_extra_viewmodes_enqueue_styles() {
	
	// Enqueue template view mode styles
	$baseurl =  plugins_url('', __FILE__);
	wp_enqueue_style( 'tainacan-extra-viewmodes-view-mode-accordion', $baseurl . '/css/_view-mode-accordion.css', [], TEATRO_EXTRA_VIEWMODES_PLUGIN_VERSION );

};

?>
