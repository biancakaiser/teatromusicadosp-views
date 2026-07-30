<template>
    <div>
        <div>
            <span v-if="!isLoading && items.length == 0" class="has-text-grey is-italic">
                {{ $i18n.get('label_no_items_found') }}
            </span>
            
            <!-- SKELETON LOADING -->
            <span class="sr-only">
                SKELETON LOADING
            </span>
            <table 
                v-if="isLoading"
                :summary="$i18n.get('label_table_of_items')"
                class="tainacan-table tainacan-table-skeleton"
                tabindex="0">
                <thead>
                    <template 
                        v-for="(column, metadatumIndex) in displayedMetadata"
                        :key="metadatumIndex">
                        <th 
                            v-if="column.display"
                            class="column-default-width"
                            :class="{
                                'thumbnail-cell': column.metadatum == 'row_thumbnail',
                                'column-small-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'date' || 
                                column.metadata_type_object.primitive_type == 'float' ||
                                column.metadata_type_object.primitive_type == 'int') : false,
                                'column-medium-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'term' || 
                                column.metadata_type_object.primitive_type == 'item' || 
                                column.metadata_type_object.primitive_type == 'compound') : false,
                                'column-large-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'long_string' || column.metadata_type_object.related_mapped_prop == 'description') : false,
                            }">
                            <div class="th-wrap">
                                {{ column.name }}
                            </div>
                        </th>
                    </template>
                </thead>
                <tbody>
                    <tr   
                        v-for="item in 12"
                        :key="item">
                        <template 
                            v-for="(column, metadatumIndex) in displayedMetadata"
                            :key="metadatumIndex">
                            <td 
                                v-if="column.display"
                                :class="{ 'thumbnail-cell': metadatumIndex == 0 }"
                                class="column-default-width skeleton" />
                        </template>
                    </tr>
                </tbody>
            </table>

            <!-- TABLE VIEW MODE -->
            <div 
                v-if="!isLoading && items.length > 0"
                class="tainacan-accordion-viewmode">
                <section
                    v-for="group in groupedItemsByCompany"
                    :key="group.key"
                    class="tainacan-accordion-group">
                    <h3 class="tainacan-accordion-heading">
                        <button
                            :id="'company-accordion-header-' + group.key"
                            type="button"
                            class="tainacan-accordion-header"
                            :aria-expanded="activeCompanyGroupKey === group.key"
                            :aria-controls="'company-accordion-panel-' + group.key"
                            @click="toggleCompanyGroup(group.key)">
                            Companhia: {{ group.label }} »
                        </button>
                    </h3>
                    <div
                        v-show="activeCompanyGroupKey === group.key"
                        :id="'company-accordion-panel-' + group.key"
                        class="tainacan-accordion-panel"
                        role="region"
                        :aria-labelledby="'company-accordion-header-' + group.key">
                        <table class="tainacan-table">
                            <thead>
                                <tr>
                                    <!-- Displayed Metadata -->
                                    <template 
                                        v-for="(column, index) in tableColumnsWithoutCompany"
                                        :key="index">
                                        <th
                                            v-if="column.display"
                                            class="column-default-width"
                                            :class="{
                                                'thumbnail-cell': column.metadatum == 'row_thumbnail',
                                                'column-needed-width column-align-right' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'float' || 
                                                column.metadata_type_object.primitive_type == 'int' ) : false,
                                                'column-small-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'date' || 
                                                column.metadata_type_object.primitive_type == 'float' ||
                                                column.metadata_type_object.primitive_type == 'int') : false,
                                                'column-medium-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'term' || 
                                                column.metadata_type_object.primitive_type == 'item') : false,
                                                'column-large-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'long_string' ||
                                                column.metadata_type_object.related_mapped_prop == 'description' || 
                                                column.metadata_type_object.primitive_type == 'compound') : false,
                                            }">
                                            <div class="th-wrap">
                                                {{ column.name }}
                                            </div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody :role="group.items.length > 1 ? 'list' : undefined">
                                <tr     
                                    v-for="(item, index) of group.items"
                                    :key="item.id ? item.id : index"
                                    :data-tainacan-item-id="item.id"
                                    :aria-setsize="totalItems"
                                    :aria-posinset="getPosInSet(getItemGlobalIndex(item, index))"
                                    :role="group.items.length > 1 ? 'listitem' : undefined">
                
                                    <!-- JS-side hook for extra content -->
                                    <td 
                                    v-if="hasBeforeHook()"
                                    class="faceted-search-hook faceted-search-hook-item-before"
                                    v-html="getBeforeHook(item)" />
                
                                    <!-- Item Displayed Metadata -->
                                    <template 
                                        v-for="(column, metadatumIndex) in tableColumnsWithoutCompany"
                                        :key="metadatumIndex">
                                        <td     
                                            v-if="column.display"
                                            class="column-default-width"
                                            :class="{
                                                'metadata-type-textarea': column.metadata_type_object != undefined && column.metadata_type_object.component == 'tainacan-textarea',
                                                'thumbnail-cell': column.metadatum == 'row_thumbnail',
                                                'column-main-content' : column.metadata_type_object != undefined ? (column.metadata_type_object.related_mapped_prop == 'title') : false,
                                                'column-needed-width column-align-right' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'float' || 
                                                column.metadata_type_object.primitive_type == 'int' ) : false,
                                                'column-small-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'date' || 
                                                column.metadata_type_object.primitive_type == 'int' ||
                                                column.metadata_type_object.primitive_type == 'float') : false,
                                                'column-medium-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'item' || 
                                                column.metadata_type_object.primitive_type == 'term' ) : false,
                                                'column-large-width' : column.metadata_type_object != undefined ? (column.metadata_type_object.primitive_type == 'long_string' ||
                                                column.metadata_type_object.primitive_type == 'compound' ||
                                                column.metadata_type_object.related_mapped_prop == 'description') : false,
                                            }">
                                            <a :href="getItemLink(item.url, getItemGlobalIndex(item, index))">
                                                <p
                                                v-if="!collectionId &&
                                                column.metadata_type_object != undefined && 
                                                column.metadata_type_object.related_mapped_prop == 'title'"
                                                v-tooltip="{
                                                    delay: {
                                                        show: 500,
                                                        hide: 300,
                                                    },
                                                    content: item.title != undefined && item.title != '' ? item.title : `<span class='has-text-grey is-italic'>` + $i18n.get('label_value_not_provided') + `</span>`,
                                                    html: true,
                                                    autoHide: false,
                                                    placement: 'auto-start',
                                                    popperClass: ['tainacan-tooltip', 'tooltip']
                                                }"
                                                :aria-label="column.name + ': ' + (item.title != undefined && item.title != '' ? item.title : $i18n.get('label_value_not_provided'))"
                                                v-html="`<span class='sr-only'>` + column.name + ': </span>' + (item.title != undefined && item.title != '' ? item.title : `<span class='has-text-grey is-italic'>` + $i18n.get('label_value_not_provided') + `</span>`)" />
                                                <p
                                                v-if="!collectionId &&
                                                column.metadata_type_object != undefined && 
                                                column.metadata_type_object.related_mapped_prop == 'description'"
                                                v-tooltip="{
                                                    delay: {
                                                        show: 500,
                                                        hide: 300,
                                                    },
                                                    content: item.description != undefined && item.description != '' ? item.description : `<span class='has-text-grey is-italic'>` + $i18n.get('label_value_not_provided') + `</span>`,
                                                    html: true,
                                                    autoHide: false,
                                                    placement: 'auto-start',
                                                    popperClass: ['tainacan-tooltip', 'tooltip']
                                                }"
                                                v-html="`<span class='sr-only'>` + column.name + ': </span>' + (item.description != undefined && item.description != '' ? item.description : `<span class='has-text-grey is-italic'>` + $i18n.get('label_value_not_provided') + `</span>`)" />
                                                <p
                                                v-if="item.metadata != undefined &&
                                                column.metadatum !== 'row_thumbnail' &&
                                                column.metadatum !== 'row_actions' &&
                                                column.metadatum !== 'row_creation' &&
                                                column.metadatum !== 'row_author' &&
                                                column.metadatum !== 'row_title' &&
                                                column.metadatum !== 'row_description'"
                                                v-tooltip="{
                                                    delay: {
                                                        show: 500,
                                                        hide: 300,
                                                    },
                                                    popperClass: [ 'tainacan-tooltip', 'tooltip', column.metadata_type_object != undefined && column.metadata_type_object.component == 'tainacan-textarea' ? 'metadata-type-textarea' : '' ],
                                                    content: renderMetadataWithLabel(item.metadata, column) != '' ? renderMetadataWithLabel(item.metadata, column) : `<span class='has-text-grey is-italic'>` + $i18n.get('label_value_not_provided') + `</span>`,
                                                    html: true,
                                                    autoHide: false,
                                                    placement: 'auto-start'
                                                }"
                                                v-html="renderMetadataWithLabel(item.metadata, column) != '' ? renderMetadataWithLabel(item.metadata, column) : `<span class='has-text-grey is-italic'>` + $i18n.get('label_value_not_provided') + `</span>`" />
                                                
                                                <span v-if="column.metadatum == 'row_thumbnail'">
                                                    <img 
                                                    :alt="item.thumbnail_alt ? item.thumbnail_alt : ''"
                                                    class="table-thumb" 
                                                    :src="$thumbHelper.getSrc(item['thumbnail'], 'tainacan-small', item.document_mimetype)">
                                                    <div class="skeleton" />
                                                </span> 
                                            </a>
                                        </td>
                                    </template>

                                    <!-- Actions -->
                                    <td 
                                        v-if="isSlideshowViewModeEnabled"
                                        class="actions-cell"
                                        :label="$i18n.get('label_actions')">
                                        <div class="actions-container">
                                            <span 
                                                v-tooltip="{
                                                    delay: {
                                                        show: 500,
                                                        hide: 100,
                                                    },
                                                    content: $i18n.get('label_see_on_fullscreen'),
                                                    placement: 'auto-start',
                                                    popperClass: ['tainacan-tooltip', 'tooltip']
                                                }"
                                                role="button"
                                                tabindex="0"
                                                :aria-label="$i18n.get('label_see_on_fullscreen')"
                                                class="icon slideshow-icon"
                                                @click.prevent="starSlideshowFromHere(getItemGlobalIndex(item, index))"
                                                @keydown.enter.prevent="starSlideshowFromHere(getItemGlobalIndex(item, index))"
                                                @keydown.space.prevent="starSlideshowFromHere(getItemGlobalIndex(item, index))">
                                                <i
                                                class="tainacan-icon tainacan-icon-viewgallery tainacan-icon-1-125em"
                                                aria-hidden="true" />
                                            </span> 
                                        </div>
                                    </td>

                                    <!-- JS-side hook for extra content -->
                                    <td 
                                    v-if="hasAfterHook()"
                                    class="faceted-search-hook faceted-search-hook-item-after"
                                    v-html="getAfterHook(item)" />
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div> 
    </div>
</template>

<script>
import qs from 'qs';

export default {
    name: 'ViewModeAccordion',
    data() {
        return {
            activeCompanyGroupKey: null,
            isSlideshowViewModeEnabled: false
        }
    },
    props: {
        collectionId: [String, Number],
        termId: [String, Number],
        displayedMetadata: Array,
        shouldHideItemsThumbnail: Boolean,
        items:  {
            type: Array,
            default: () => [],
            required: true
        },
        isLoading: false,
        totalItems: Number,
        enabledViewModes: Array,
        containerId: String,
    },
    computed: {
        queries() {
            let currentQueries = (this.$route && this.$route.query) ? JSON.parse(JSON.stringify(this.$route.query)) : {};
            if (currentQueries) {
                delete currentQueries['view_mode'];
                delete currentQueries['fetch_only'];
                delete currentQueries['fetch_only_meta'];
            }
            return currentQueries
        },
         /* 
         * This computed property only returns the metadatum object where the title is. 
         * In repository level, there is not "title metadatum", this information comes directly from the item.title.
         */
         titleItemMetadatum() {
            const possibleTitleItemMetadatum = (this.displayedMetadata || []).find(aMetadatum => aMetadatum.display && aMetadatum.metadata_type_object && aMetadatum.metadata_type_object.related_mapped_prop == 'title');
            return possibleTitleItemMetadatum ? possibleTitleItemMetadatum : false;
        },
        /* 
         * This computed property only returns the metadatum object where the description is. 
         * In repository level, there is not "description metadatum", this information comes directly from the item.description.
         */
         descriptionItemMetadatum() {
            const possibleDescriptionItemMetadatum = (this.displayedMetadata || []).find(aMetadatum => aMetadatum.display && aMetadatum.metadata_type_object && aMetadatum.metadata_type_object.related_mapped_prop == 'description');
            return possibleDescriptionItemMetadatum ? possibleDescriptionItemMetadatum : false;
        },
        companyColumn() {
            return (this.displayedMetadata || []).find(column => {
                const columnName = column && column.name ? this.stripHtml(column.name).trim().toLowerCase() : '';
                const metadataName = column && column.metadata_type_object && column.metadata_type_object.name ? this.stripHtml(column.metadata_type_object.name).trim().toLowerCase() : '';
                const slug = column && column.slug ? column.slug.toString().trim().toLowerCase() : '';

                return columnName == 'companhia' || metadataName == 'companhia' || slug == 'companhia';
            }) || false;
        },
        tableColumnsWithoutCompany() {
            return (this.displayedMetadata || []).filter(column => column !== this.companyColumn);
        },
        groupedItemsByCompany() {
            const groups = [];
            const groupsByKey = {};

            (this.items || []).forEach(item => {
                const key = this.getCompanyGroupKey(item);

                if (!groupsByKey[key]) {
                    groupsByKey[key] = {
                        key: key,
                        label: this.getCompanyGroupLabel(item),
                        items: []
                    };
                    groups.push(groupsByKey[key]);
                }

                groupsByKey[key].items.push(item);
            });

            return groups;
        },
    },
    mounted() {
        this.isSlideshowViewModeEnabled = (this.enabledViewModes && Array.isArray(this.enabledViewModes)) ? (this.enabledViewModes.findIndex((viewMode) => viewMode == 'slideshow') >= 0) : false;
    },
    watch: {
        groupedItemsByCompany: {
            immediate: true,
            handler(groups) {
                if (!groups || !groups.length) {
                    this.activeCompanyGroupKey = null;
                    return;
                }

                const activeGroupStillExists = groups.some(group => group.key == this.activeCompanyGroupKey);

                if (!this.activeCompanyGroupKey || !activeGroupStillExists)
                    this.activeCompanyGroupKey = groups[0].key;
            }
        }
    },
    methods: {
        hasBeforeHook() {
            if (typeof wp !== 'undefined' && wp.hooks !== undefined)
                return wp.hooks.hasFilter(`tainacan_faceted_search_item_before`) || wp.hooks.hasFilter(`tainacan_faceted_search_collection_${this.collectionId}_item_before`);

            return false;
        },
        hasAfterHook() {
            if (typeof wp !== 'undefined' && wp.hooks !== undefined)
                return wp.hooks.hasFilter(`tainacan_faceted_search_collection_item_after`) || wp.hooks.hasFilter(`tainacan_faceted_search_collection_${this.collectionId}_item_after`);

            return false;
        },
        getBeforeHook(item) {
            if (typeof wp !== 'undefined' && wp.hooks !== undefined)
                return wp.hooks.applyFilters(`tainacan_faceted_search_collection_${this.collectionId}_item_before`, wp.hooks.applyFilters(`tainacan_faceted_search_item_before`, '', item), item);

            return '';
        },
        getAfterHook(item) {
            if (typeof wp !== 'undefined' && wp.hooks !== undefined)
                return wp.hooks.applyFilters(`tainacan_faceted_search_collection_${this.collectionId}_item_after`, wp.hooks.applyFilters(`tainacan_faceted_search_item_after`, '', item), item);

            return '';
        },
        getItemLink(itemUrl, index) {
            // Check if query parameters should be included based on the setting
            const enableQueryParams = (typeof tainacan_blocks !== 'undefined' && tainacan_blocks.enable_item_link_query_params !== undefined) 
                ? tainacan_blocks.enable_item_link_query_params 
                : true; // Default to true to maintain current behavior
            
            if (this.queries && enableQueryParams) {
                // Inserts information necessary for item by item navigation on single pages
                this.queries['pos'] = ((this.queries['paged'] - 1) * this.queries['perpage']) + index;
                if ( isNaN(Number(this.queries['pos'])) )
                    delete this.queries['pos'];
                
                this.queries['source_list'] = this.termId ? 'term' : (!this.collectionId || this.collectionId == 'default' ? 'repository' : 'collection');
                
                if ( this.queries['source_list'] == 'term' || this.queries['source_list'] == 'collection' )
                    this.queries['source_entity_id'] = this.termId ? this.termId : this.collectionId;

                if ( this.$route && this.$route.href && this.$route.href.split('?') && this.$route.href.split('?').length )
                    this.queries['ref'] = this.$route.href;
                
                return itemUrl + '?' + qs.stringify(this.queries);
            }
            return itemUrl;
        },
        renderMetadata(item, metadatum, multivalueIndex) {
            let metadata = false;
            if (item && item.metadata && item.metadata[metadatum.slug] != undefined)
                metadata = item.metadata[metadatum.slug] 
            else if (metadatum &&
                     metadatum.metadata_type_object &&
                     metadatum.metadata_type_object.core && 
                     metadatum.metadata_type_object.related_mapped_prop &&
                     item[metadatum.metadata_type_object.related_mapped_prop]
            ) {
                return item[metadatum.metadata_type_object.related_mapped_prop];
            }

            if (!metadata)
                return '';
                
            if ( multivalueIndex != undefined && metadata.value[multivalueIndex]) {
            
                if ( !Array.isArray(metadata.value[multivalueIndex]) && metadata.value[multivalueIndex].value_as_html)
                    return metadata.value[multivalueIndex].value_as_html;

                if ( Array.isArray(metadata.value[multivalueIndex]) ) {
                    let sumOfValuesAsHtml = '';

                    metadata.value[multivalueIndex].forEach(aValue => {
                        if (aValue.value_as_html)
                            sumOfValuesAsHtml += aValue.value_as_html + '<br>';
                    })

                    return sumOfValuesAsHtml;
                }
            }

            return metadata.value_as_html;
        },
        starSlideshowFromHere(index) {
            if ( this.$router && this.$route && this.$route.query )
                this.$router.replace({ query: {...this.$route.query, ...{'slideshow-from': index } }}).catch(() => {});
        },
        getPosInSet(index) {
            if ( !isNaN(Number(this.queries.paged)) && !isNaN(Number(this.queries.perpage)) )
                return ((Number(this.queries.paged) - 1) * Number(this.queries.perpage)) + index + 1;
        },
        getItemGlobalIndex(item, fallbackIndex) {
            const itemIndex = (this.items || []).indexOf(item);
            return itemIndex >= 0 ? itemIndex : fallbackIndex;
        },
        toggleCompanyGroup(groupKey) {
            this.activeCompanyGroupKey = this.activeCompanyGroupKey == groupKey ? null : groupKey;
        },
        normalizeMetadataCollection(metadata) {
            if (!metadata)
                return [];

            if (Array.isArray(metadata))
                return metadata.filter(aMetadatum => aMetadatum);

            return Object.keys(metadata)
                .map(metadataKey => metadata[metadataKey])
                .filter(aMetadatum => aMetadatum);
        },
        getCompanyMetadata(item) {
            if (!item || !item.metadata)
                return false;

            const companyColumn = this.companyColumn;

            if (companyColumn && !Array.isArray(item.metadata)) {
                const possibleKeys = [
                    companyColumn.slug,
                    companyColumn.metadatum,
                    companyColumn.id
                ].filter(possibleKey => possibleKey != undefined && possibleKey !== '');

                for (let index = 0; index < possibleKeys.length; index++) {
                    const possibleKey = possibleKeys[index];

                    if (item.metadata[possibleKey] != undefined)
                        return item.metadata[possibleKey];
                }
            }

            return this.normalizeMetadataCollection(item.metadata).find(metadata => {
                const metadataName = metadata && metadata.name ? this.stripHtml(metadata.name).trim().toLowerCase() : '';
                return metadataName == 'companhia';
            }) || false;
        },
        getCompanyGroupLabel(item) {
            const metadata = this.getCompanyMetadata(item);

            if (!metadata)
                return 'Sem companhia';

            if (metadata.value_as_string && metadata.value_as_string.toString().trim() != '')
                return metadata.value_as_string.toString().trim();

            const htmlText = this.stripHtml(metadata.value_as_html);

            return htmlText != '' ? htmlText : 'Sem companhia';
        },
        getCompanyGroupKey(item) {
            const label = this.getCompanyGroupLabel(item);
            let normalizedLabel = label
                .toString()
                .trim()
                .toLowerCase();

            if (typeof normalizedLabel.normalize == 'function')
                normalizedLabel = normalizedLabel.normalize('NFD');

            normalizedLabel = normalizedLabel
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');

            return normalizedLabel != '' ? normalizedLabel : 'sem-companhia';
        },
        stripHtml(html) {
            if (!html)
                return '';

            if (typeof document == 'undefined')
                return html.toString().replace(/<[^>]*>/g, '').trim();

            const temporaryElement = document.createElement('div');
            temporaryElement.innerHTML = html;

            return (temporaryElement.textContent || temporaryElement.innerText || '').trim();
        },
        renderMetadataWithLabel(itemMetadata, column) {
            if (!itemMetadata || !column)
                return '';

            let metadata = false;

            if (!Array.isArray(itemMetadata) && column.slug != undefined && itemMetadata[column.slug] != undefined)
                metadata = itemMetadata[column.slug];

            if (!metadata)
                metadata = this.normalizeMetadataCollection(itemMetadata).find(aMetadatum => aMetadatum && aMetadatum.id != undefined && column.id != undefined && aMetadatum.id == column.id);

            if (!metadata)
                return '';

            const value = metadata.value_as_html ? metadata.value_as_html : (metadata.value_as_string ? metadata.value_as_string : '');

            if (value == '')
                return '';

            return '<span class="sr-only">' + column.name + ': </span>' + value;
        }
    }
}
</script>
