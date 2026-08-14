window.tainacan_extra_components = typeof window.tainacan_extra_components != "undefined" ? window.tainacan_extra_components : {};

const TainacanMetadataTypeCustom = {
    name: "TainacanMetadataTypeCustom",
    props: {
        itemMetadatum: Object,
        value: [String, Number, Array],
        disabled: false,
    },
    computed: {
        getStep: function() {
            if (this.itemMetadatum && this.itemMetadatum.metadatum.metadata_type_options && this.itemMetadatum.metadatum.metadata_type_options.step)
                return this.itemMetadatum.metadatum.metadata_type_options.step;
            else
                return 0.01;
        }
    },
    methods: {
        onInput: function(value) {
            this.$emit('input', value);
        },
        onBlur: function() {
            this.$emit('blur');
        }
    },
    template: `
    <b-input
            :disabled="disabled"
            :id="itemMetadatum.metadatum.metadata_type_object.component + '-' + itemMetadatum.metadatum.slug"
            :value="value"
            @input="onInput($event)"
            @blur="onBlur"
            type="number"
            lang="en"
            :step="getStep"/>
    `
}

window.tainacan_extra_components["tainacan-metadata-type-custom"] = TainacanMetadataTypeCustom;
