<?php

namespace TeatroMusicadoSP\Customizations\Blocks;

use TeatroMusicadoSP\Customizations\Contracts\Module;
use TeatroMusicadoSP\Customizations\Traits\Singleton;

// Evita acesso direto ao arquivo
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Bibliographic implements Module
{
    use Singleton;

    public $metadataFolderURL = TMSP_CUSTOMIZATIONS_URL . 'classes/blocks/';
    public $metadataFolderPath = TMSP_CUSTOMIZATIONS_PATH . 'classes/blocks/';

    public function register(): void {
        add_filter( 'the_content', array($this, 'add_bibliographic_block'), 20 );
    }
 
    function add_bibliographic_block ($content) {
        if ( ! function_exists( 'tainacan_get_item' ) || ! is_singular() ) {
            return $content;
        }

        global $post;
        $item = \Tainacan\Repositories\Items::get_instance()->fetch( $post->ID );

        if ( ! $item ) {
            return $content;
        }

        $titulo = $item->get_title();
        $url    = get_permalink( $post->ID );
        $data   = date_i18n( 'd/m/Y' );

        $bloco = '<div class="citacao-referencia">';
        $bloco .= '<h3>Como citar</h3>';
        $bloco .= '<p>' . esc_html( $titulo ) . ' In: TEATRO musicado em São Paulo (1914-1934). Coord. por Virgínia de Almeida Bessa. <br/>';
        $bloco .= 'Disponível em: <a href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>. Acesso em: ' . esc_html( $data ) . '</p>';
        $bloco .= '</div>';

        return $content . $bloco;
    }
}
