<?php

namespace TeatroMusicadoSP\Customizations\MetadataTypes\SlugId;

/**
 * Class SlugIdMetadataType
 * This is inside "slug-id/metadata-type.php"
 */
class SlugIdMetadataType extends \Tainacan\Metadata_Types\Metadata_Type {

    function __construct() {

        parent::__construct();

        // Basic options
        $this->set_name( __('ID Numérico', 'slug-id-metadata-type') );
        $this->set_description( __('A numeric value, integer or float', 'slug-id-metadata-type') );
        $this->set_primitive_type(['float']);
        $this->set_component('metadata-type-slug-id-component');
        $this->set_preview_template('
            <div>
                <div class="control is-clearfix">
                    <input type="number" placeholder="0123" class="input">
                </div>
            </div>
        ');

        // For custom Metadata Type Options
        $this->set_form_component('metadata-type-slug-id-form-component');
        $this->set_default_options([
            'step' => 1
        ]);
    }
    
    public function get_form_labels(){
        return [
            'step' => [
                'title' => __( 'Step', 'tainacan' ),
                'description' => __( 'The amount to be increased or decreased when clicking on filter control buttons.', 'tainacan' ),
            ]
        ];
    }
    
    public function validate_options(\Tainacan\Entities\Metadatum $metadatum) {

        $option = $this->get_option('step');

        if (is_numeric($option)) { // Or any other validation condition
            return true; // validated!
        } else {
            return ['step' => __('The option "step" is invalid. Must be a number!', 'slug-id-metadata-type')];
        }
    }
}
?>
