<?php

namespace TeatroMusicadoSP\Customizations\MetadataTypes;

use TeatroMusicadoSP\Customizations\Contracts\Module;
use TeatroMusicadoSP\Customizations\Traits\Singleton;

// Evita acesso direto ao arquivo
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class RegisterMetadatas implements Module
{
    use Singleton;

    public $metadataFolderURL = TMSP_CUSTOMIZATIONS_URL . 'classes/metadata-types/';
    public $metadataFolderPath = TMSP_CUSTOMIZATIONS_PATH . 'classes/metadata-types/';

    public function register(): void {

		add_action( 'tainacan-register-vuejs-component', array( $this, 'register_metadata_type_form' ) );
        add_filter( 'wp_unique_post_slug', array($this, 'filter_post_slug'), 10, 6 );
    }

    function register_metadata_type( $helper ) {
        // Registering the Class
        require_once( $this->metadataFolderPath . 'slug-id/slug-id-metadata-type.php' );

        // Registering the Vue Component
        $handle = 'metadata-type-slug-id-component';
        $class_name = \TeatroMusicadoSP\Customizations\MetadataTypes\SlugId\SlugIdMetadataType::class;
        $metadata_script_url = $this->metadataFolderURL . 'slug-id/slug-id-metadata-type.js';
        $helper->register_metadata_type($handle, $class_name, $metadata_script_url);
	}

    function register_metadata_type_form( $helper ) {
        // Registering the Vue Component for the Metadata Options Form
        $handle2 = 'metadata-type-slug-id-form-component';
        $component_script_url = $this->metadataFolderURL . 'slug-id/slug-id-metadata-type-form.js';
        $helper->register_vuejs_component($handle2, $component_script_url);
    }
 
    /**
     * Usa do filtro 'wp_unique_post_slug' para substituir o slug do post pelo id cadastrado 
     * no metadado, caso o slug seja puramente numérico e não haja conflito real.
     */
    function filter_post_slug ($slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug) {
        // só atua quando o slug original era puramente numérico
        if ( ! is_numeric( $original_slug ) ) {
            return $slug;
        }

        // se o slug não mudou, não há nada a corrigir
        if ( $slug === $original_slug ) {
            return $slug;
        }

        global $wpdb;

        // verifica se existe REALMENTE um conflito com esse slug numérico
        $conflito = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_name FROM {$wpdb->posts}
            WHERE post_name = %s
            AND post_type = %s
            AND ID != %d
            AND post_parent = %d
            LIMIT 1",
            $original_slug, $post_type, $post_ID, $post_parent
        ));

        // sem conflito real: devolve o slug numérico original, sem o "-2"
        if ( ! $conflito ) {
            return $original_slug;
        }

        // conflito real existe: mantém o comportamento padrão do WP (com sufixo)
        return $slug;
    }
}
