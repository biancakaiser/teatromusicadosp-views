<?php

namespace TeatroMusicadoSP\Customizations\ViewModes;

use TeatroMusicadoSP\Customizations\Contracts\Module;
use TeatroMusicadoSP\Customizations\Traits\Singleton;

// Evita acesso direto ao arquivo
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Companies implements Module
{
    use Singleton;

    public $componentsFolderURL = TMSP_CUSTOMIZATIONS_URL . 'components/';
    public $componentsFolderPath = TMSP_CUSTOMIZATIONS_PATH . 'components/';

    public function register(): void {
        add_action( 'tainacan-register-vuejs-component', array( $this, 'register_company_viewmode_components' ) );
        add_action( 'wp_print_scripts', array($this, 'company_viewmode_components_enqueue_styles') );
    }
 
    function register_company_viewmode_components($helper) {

        if ( function_exists( 'tainacan_register_view_mode' ) ) {

            // Registering the Vue Component
            $handle = 'teatro-companies-viewmode';

            // While `npm run dev` (webpack-dev-server) is running, load the bundle straight from
            // it instead of the built file, so edits hot-reload. Enable by adding
            // define('TEATRO_COMPONENTS_DEV_SERVER', true); to wp-config.php.
            $component_script_url = ( defined('TEATRO_COMPONENTS_DEV_SERVER') && TEATRO_COMPONENTS_DEV_SERVER )
                ? 'http://127.0.0.1:8080/components.bundle.js'
                : $this->componentsFolderURL . '/build/components.bundle.js';

            $helper->register_vuejs_component($handle, $component_script_url, [ 'public' => true, 'deps' => ['wp-i18n'] ], null, true);
            
            // Registering the view mode
            tainacan_register_view_mode('companies-viewmode', [
                'label' 				=> 'Companhias',
                'description' 			=> __('Companhias view mode.', 'customizations-teatromusicadosp'),
                'icon' 					=> '<span class="icon"><i><svg fill="var(--tainacan-info-color, #555758)" xmlns="http://www.w3.org/2000/svg" height="24" width="24"><path d="M8.492 6.074h7.016v11.852H8.492zM4.943 7.477h2.806v9.046H4.943zM16.251 7.477h2.807v9.046H16.25zM19.8 8.442h1.884v7.116h-1.883zM2.316 8.442h1.883v7.116H2.316z"/></svg></i></span>',
                'type' 					=> 'component',
                'component' 			=> 'view-mode-companies',
                'dynamic_metadata' 		=> true,
                'implements_skeleton' 	=> true
            ]);
        }
    }

    function company_viewmode_components_enqueue_styles() {
	
        // Enqueue template view mode styles
        wp_enqueue_style( 'tainacan-extra-viewmodes-view-mode-companies', $this->componentsFolderURL . '/css/_view-mode-companies.css', [], TMSP_CUSTOMIZATIONS_VERSION );

    }
}
