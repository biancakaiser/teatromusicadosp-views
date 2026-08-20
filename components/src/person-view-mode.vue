<template>
    <component
        :is="resolvedTemplate"
        v-bind="$props" />
</template>

<script>
import MembersTemplate from './members-template.vue';
import ConceptionTemplate from './conception-template.vue';

/*
 * IDs das coleções cujos metadados de Relacionamento apontam para Pessoas
 * (3408) e aparecem agrupados na seção "Itens relacionados a este" do item
 * de Pessoa. Mesma referência usada em
 * classes/related-items/pessoa-related-items-order.php, que ordena esses
 * mesmos grupos por coleção.
 */
const MEMBROS_COLLECTION_ID = 4859;
const PECAS_COLLECTION_ID = 3415;
const ESPETACULOS_COLLECTION_ID = 3922;

export default {
    name: 'PersonViewMode',
    props: {
        collectionId: [String, Number],
        termId: [String, Number],
        displayedMetadata: Array,
        shouldHideItemsThumbnail: Boolean,
        items: {
            type: Array,
            default: () => [],
            required: true
        },
        isLoading: Boolean,
        totalItems: Number,
        enabledViewModes: Array,
        containerId: String,
    },
    data() {
        return {
            noComponent: "Nenhum componente encontrado!",
        }
    },
    computed: {
        /*
         * Tainacan monta uma instância Vue independente por coleção
         * relacionada (cada grupo "Membros"/"Peças"/"Espetáculos" tem seu
         * próprio data-collection-id), então a prop collectionId já chega
         * aqui identificando de qual coleção relacionada este grupo é.
         */
        resolvedTemplate() {
            const collectionId = Number(this.collectionId);

            if (collectionId === MEMBROS_COLLECTION_ID)
                return MembersTemplate;

            if (collectionId === PECAS_COLLECTION_ID || collectionId === ESPETACULOS_COLLECTION_ID)
                return ConceptionTemplate;

            return this.noComponent;
        }
    }
}
</script>
