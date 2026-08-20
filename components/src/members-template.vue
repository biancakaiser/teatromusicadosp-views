<template>
    <div class="professional-history-wrapper">

        <!-- SKELETON LOADING -->
        <div
            v-if="isLoading"
            class="professional-history professional-history--loading">
            <span
                class="sr-only"
                role="status">
                Carregando conteúdo…
            </span>
            <div class="professional-history__header">
                <div class="skeleton-bar skeleton-bar--eyebrow" />
                <div class="skeleton-bar skeleton-bar--title" />
            </div>
            <div class="company-accordion">
                <div
                    v-for="skeletonIndex in 4"
                    :key="skeletonIndex"
                    class="company-accordion__item company-accordion__item--skeleton">
                    <div class="skeleton-bar skeleton-bar--trigger" />
                </div>
            </div>
        </div>

        <p
            v-else-if="recordCount == 0"
            class="professional-history__empty has-text-grey is-italic">
            {{ $i18n.get('label_no_items_found') }}
        </p>

        <!-- ACCORDION + TIMELINE VIEW MODE -->
        <section
            v-else
            class="professional-history">
            <header class="professional-history__header">
                <h2 class="professional-history__title">Companhias com as quais atuou em São Paulo</h2>
                <p class="professional-history__summary">{{ collectionSummaryLabel }}</p>
            </header>

            <div class="company-accordion">
                <section
                    v-for="company in companyAccordionItems"
                    :key="company.key"
                    class="company-accordion__item">
                    <h3 class="company-accordion__heading">
                        <button
                        :id="company.headerId"
                        type="button"
                        class="company-accordion__trigger"
                        :aria-expanded="activeCompanyGroupKey === company.key"
                        :aria-controls="company.panelId"
                        @click="toggleCompanyGroup(company.key)">
                            <span
                                v-if="company.label"
                                class="company-accordion__label">{{ company.label }}</span>
                            <span
                                v-if="company.link"
                                class="company-accordion__link"
                                v-html="company.link" />
                            <span class="company-accordion__summary">
                                <span class="company-accordion__count">  |  {{ company.recordCountLabel }}</span>
                                <span
                                    v-if="company.dateRangeLabel"
                                    class="company-accordion__dates">  |  {{ company.dateRangeLabel }}</span>
                            </span>
                            <span
                                class="company-accordion__icon"
                                aria-hidden="true" />
                        </button>
                    </h3>
                    <div
                        v-show="activeCompanyGroupKey === company.key"
                        :id="company.panelId"
                        class="company-accordion__panel"
                        role="region"
                        :aria-expanded="activeCompanyGroupKey === company.key"
                        :aria-labelledby="company.headerId">
                        <ol
                            class="professional-timeline"
                            role="list">
                            <li
                                v-for="record in company.records"
                                :key="record.item.id != undefined ? record.item.id : record.index"
                                class="professional-timeline__item"
                                :data-tainacan-item-id="record.item.id"
                                :aria-setsize="totalItems"
                                :aria-posinset="getPosInSet(getItemGlobalIndex(record.item, record.index))">

                                <!-- JS-side hook for extra content -->
                                <div
                                    v-if="hasBeforeHook()"
                                    class="professional-timeline__hook faceted-search-hook-item-before"
                                    v-html="getBeforeHook(record.item)" />

                                <div class="professional-timeline__content">
                                    <div class="professional-timeline__meta">
                                        <span class="professional-timeline__dates">{{ record.dateRangeLabel || 'Data não informada' }}</span>
                                    </div>

                                    <div class="professional-timeline__body">

                                        <div class="professional-timeline__info">
                                            <ul
                                                v-if="record.castMembers.length"
                                                class="cast-list">
                                                <li
                                                    v-for="castMember in record.castMembers"
                                                    :key="castMember.key"
                                                    class="cast-list__item">
                                                    <!-- <span
                                                        v-if="castMember.personHtml"
                                                        class="cast-list__person"
                                                        v-html="castMember.personHtml" />
                                                    <span
                                                        v-else
                                                        class="cast-list__person cast-list__person--placeholder">Pessoa não informada</span> -->
                                                    <span
                                                        v-if="castMember.roleText"
                                                        class="cast-list__role">{{ castMember.roleText }}</span>
                                                </li>
                                            </ul>
                                            <p
                                                v-else
                                                class="cast-list__empty">Nenhum elenco registrado</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- JS-side hook for extra content -->
                                <div
                                    v-if="hasAfterHook()"
                                    class="professional-timeline__hook faceted-search-hook-item-after"
                                    v-html="getAfterHook(record.item)" />
                            </li>
                        </ol>
                    </div>
                </section>
            </div>
        </section>
    </div>
</template>

<script>
import qs from 'qs';

export default {
    name: 'MembersTemplate',
    data() {
        return {
            activeCompanyGroupKey: null,
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
        companyColumn() {
            return (this.displayedMetadata || []).find(column => {
                const columnName = column && column.name ? this.stripHtml(column.name).trim().toLowerCase() : '';
                const metadataName = column && column.metadata_type_object && column.metadata_type_object.name ? this.stripHtml(column.metadata_type_object.name).trim().toLowerCase() : '';
                const slug = column && column.slug ? column.slug.toString().trim().toLowerCase() : '';

                return columnName == 'companhia' || metadataName == 'companhia' || slug == 'companhia';
            }) || false;
        },
        /*
         * Every displayed metadatum other than the company one. Used to locate the
         * date and cast (compound) columns without repeating the filter each time.
         */
        metadataColumns() {
            return (this.displayedMetadata || []).filter(column => column && column !== this.companyColumn);
        },
        dateColumns() {
            return this.metadataColumns.filter(column => column.metadata_type_object && column.metadata_type_object.primitive_type == 'date');
        },
        initialDateColumn() {
            const initialDate = this.findColumnByPattern(this.dateColumns, /inicial/i) || this.dateColumns[0] || false;
            return initialDate;
        },
        finalDateColumn() {
            const finalDate = this.findColumnByPattern(this.dateColumns, /final/i) || this.dateColumns[0] || false;
            return finalDate;
        },
        castColumn() {
            const compoundColumns = this.metadataColumns.filter(column => column.metadata_type_object && column.metadata_type_object.primitive_type == 'compound');
            return this.findColumnByPattern(compoundColumns, /elenco/i) || compoundColumns[0] || false;
        },
        /*
         * On a Person single item page, the URL carries that person's slug
         * (.../pessoas/{slug}/). When present, records/castMembers are scoped
         * down to that person; outside of a person page this is '' and every
         * record/castMember is shown, matching the plain listing view mode.
         */
        currentPersonSlug() {
            if (typeof window == 'undefined' || !window.location || !window.location.pathname)
                return '';

            const match = window.location.pathname.match(/\/pessoas\/([^/]+)\/?/i);
            return match ? decodeURIComponent(match[1]).toLowerCase() : '';
        },
        visibleItems() {
            if (!this.currentPersonSlug)
                return this.items || [];

            return (this.items || []).filter(item => this.getCastMembers(item).length > 0);
        },
        groupedItemsByCompany() {
            const groups = [];
            const groupsByKey = {};

            this.visibleItems.forEach(item => {
                const key = this.getCompanyGroupKey(item);
                const metadata = this.getCompanyMetadata(item);

                if (!groupsByKey[key]) {
                    groupsByKey[key] = {
                        key: key,
                        label: this.getMetadataValue(metadata, "string"),
                        link: this.getMetadataValue(metadata, "html"),
                        items: []
                    };
                    groups.push(groupsByKey[key]);
                }

                groupsByKey[key].items.push(item);
            });

            return groups;
        },
        companyCount() {
            return this.groupedItemsByCompany.length;
        },
        recordCount() {
            return this.visibleItems.length;
        },
        shouldOpenFirstCompanyByDefault() {
            return this.companyCount > 0 && this.companyCount <= 4;
        },
        collectionSummaryLabel() {
            const companyLabel = this.companyCount == 1 ? '1 companhia' : this.companyCount + ' companhias';
            const recordLabel = this.recordCount == 1 ? '1 vínculo registrado' : this.recordCount + ' vínculos registrados';
            return companyLabel + ' · ' + recordLabel;
        },
        /*
         * Enriches each company group with the pre-computed values the template needs
         * (ids, summary labels, and per-record date range / cast / thumbnail), so the
         * template itself stays free of repeated method calls.
         */
        companyAccordionItems() {
            return this.groupedItemsByCompany.map(group => {
                const sortedItems = this.getItemsSortedByFinalDate(group.items);
                const records = sortedItems.map((item, index) => ({
                    item,
                    index,
                    dateRangeLabel: this.getItemDateRangeLabel(item),
                    castMembers: this.getCastMembers(item),
                    thumbnailSrc: this.getThumbnailSrc(item)
                }));

                return {
                    key: group.key,
                    label: group.label,
                    link: group.link,
                    records: records,
                    headerId: this.getAccordionHeaderId(group.key),
                    panelId: this.getAccordionPanelId(group.key),
                    recordCountLabel: this.getRecordCountLabel(group.items.length),
                    dateRangeLabel: this.getGroupDateRangeLabel(group.items)
                };
            });
        },
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

                if (activeGroupStillExists)
                    return;

                this.activeCompanyGroupKey = (groups.length <= 4) ? groups[0].key : null;
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
        getAccordionHeaderId(groupKey) {
            return (this.containerId || 'professional-history') + '-company-header-' + groupKey;
        },
        getAccordionPanelId(groupKey) {
            return (this.containerId || 'professional-history') + '-company-panel-' + groupKey;
        },
        getRecordCountLabel(count) {
            return count == 1 ? '1 vínculo' : count + ' vínculos';
        },
        findColumnByPattern(columns, pattern) {
            return (columns || []).find(column => {
                const name = column && column.name ? this.stripHtml(column.name).trim() : '';
                const slug = column && column.slug ? column.slug.toString().trim() : '';
                return pattern.test(name) || pattern.test(slug);
            }) || false;
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
        getColumnMetadata(item, column) {
            if (!item || !item.metadata || !column)
                return false;

            const possibleKeys = [
                column.slug,
                column.metadatum,
                column.id
            ].filter(possibleKey => possibleKey != undefined && possibleKey !== '');

            for (let index = 0; index < possibleKeys.length; index++) {
                const possibleKey = possibleKeys[index];

                if (item.metadata[possibleKey] != undefined)
                    return item.metadata[possibleKey];
            }

            return false;
        },
        getCompanyMetadata(item) {
            if (!item || !item.metadata)
                return false;

            const companyColumn = this.companyColumn;

            if (companyColumn && !Array.isArray(item.metadata)) {
                const metadata = this.getColumnMetadata(item, companyColumn);
                if (metadata)
                    return metadata;
            }

            return this.normalizeMetadataCollection(item.metadata).find(metadata => {
                const metadataName = metadata && metadata.name ? this.stripHtml(metadata.name).trim().toLowerCase() : '';
                return metadataName == 'companhia';
            }) || false;
        },
        getMetadataValue(metadata, valueType) {
            if (!metadata)
                return 'Sem Metadados';

            if (valueType == "string"){
                if (metadata.value_as_string && metadata.value_as_string != '')
                    return metadata.value_as_string;
            }

            if (valueType == "html"){
                if (metadata.value_as_html && metadata.value_as_html != '')
                    return metadata.value_as_html;
            }

            return 'Valor não encontrado';
        },
        getCompanyGroupKey(item) {
            const label = this.getMetadataValue(this.getCompanyMetadata(item), "string");
            let normalizedLabel = label
                .toString()
                .trim()
                .toLowerCase();

            if (typeof normalizedLabel.normalize == 'function')
                normalizedLabel = normalizedLabel.normalize('NFD');

            normalizedLabel = normalizedLabel
                .replace(new RegExp('[\\u0300-\\u036f]', 'g'), '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');

            return normalizedLabel != '' ? normalizedLabel : 'sem-companhia';
        },
        getSortableDateTimestamp(item, column) {
            const metadata = this.getColumnMetadata(item, column);
            if (!metadata || !metadata.value)
                return null;

            const rawValue = Array.isArray(metadata.value) ? metadata.value[0] : metadata.value;
            if (!rawValue)
                return null;

            const timestamp = Date.parse(rawValue.toString().replace(' ', 'T'));
            return isNaN(timestamp) ? null : timestamp;
        },
        getItemDateRangeLabel(item) {
            const start = this.getMetadataValue(this.getColumnMetadata(item, this.initialDateColumn), "string");
            const end = this.getMetadataValue(this.getColumnMetadata(item, this.finalDateColumn), "string");

            if (start && end && start != end)
                return start + ' – ' + end;

            return start || end || '';
        },
        /*
         * Orders a company's records chronologically (oldest final date first),
         * so the timeline <li>s render from earliest to most recent period.
         * Falls back to the initial date column when the final date is missing,
         * and keeps undated records at the end, in their original order.
         */
        getItemsSortedByFinalDate(items) {
            return (items || [])
                .map((item, index) => {
                    let timestamp = this.getSortableDateTimestamp(item, this.finalDateColumn);
                    if (timestamp == null)
                        timestamp = this.getSortableDateTimestamp(item, this.initialDateColumn);
                    return { item, index, timestamp };
                })
                .sort((a, b) => {
                    if (a.timestamp == null && b.timestamp == null)
                        return a.index - b.index;
                    if (a.timestamp == null)
                        return 1;
                    if (b.timestamp == null)
                        return -1;

                    return a.timestamp - b.timestamp;
                })
                .map(entry => entry.item);
        },
        getGroupDateRangeLabel(items) {
            const datedValues = [];

            (items || []).forEach(item => {
                [this.initialDateColumn, this.finalDateColumn].forEach(column => {
                    const timestamp = this.getSortableDateTimestamp(item, column);
                    const label = this.getMetadataValue(this.getColumnMetadata(item, column), "string");
                    if (timestamp != null && label)
                        datedValues.push({ timestamp, label });
                });
            });

            if (!datedValues.length)
                return '';

            datedValues.sort((a, b) => a.timestamp - b.timestamp);

            const earliest = datedValues[0].label;
            const latest = datedValues[datedValues.length - 1].label;

            return earliest == latest ? earliest : earliest + ' – ' + latest;
        },
        /*
         * Compound "cast" metadata arrives from the API as pre-rendered HTML: each
         * repetition wrapped in .tainacan-compound-metadatum, each field wrapped in
         * .tainacan-metadatum with a .child-metadatum-label/.child-metadatum-value pair
         * (see Tainacan's Compound metadata type get_value_as_html()). Parsing that
         * structure lets us render "Pessoa"/"Função" as separate entries instead of the
         * flattened "Pessoa: Name Função: Role" string.
         */
        getCastMembers(item) {
            const metadata = this.getColumnMetadata(item, this.castColumn);
            if (!metadata || !metadata.value_as_html || typeof document == 'undefined')
                return [];

            const container = document.createElement('div');
            container.innerHTML = metadata.value_as_html;

            const groupElements = container.querySelectorAll('.tainacan-compound-metadatum');
            const groups = groupElements.length ? Array.from(groupElements) : [container];

            return groups.map((groupElement, groupIndex) => {
                const person = this.extractCastField(groupElement, /pessoa|nome|artista/i);
                const role = this.extractCastField(groupElement, /fun[cç][aã]o|papel|cargo/i);

                return {
                    key: (item.id != undefined ? item.id : 'item') + '-cast-' + groupIndex,
                    personHtml: person.html,
                    personText: person.text,
                    roleText: role.text
                };
            }).filter(castMember => (castMember.personText || castMember.roleText) && this.castMemberMatchesCurrentPerson(castMember));
        },
        castMemberMatchesCurrentPerson(castMember) {
            if (!this.currentPersonSlug)
                return true;

            return this.getPersonHtmlSlugs(castMember.personHtml).includes(this.currentPersonSlug);
        },
        getPersonHtmlSlugs(personHtml) {
            if (!personHtml || typeof document == 'undefined')
                return [];

            const container = document.createElement('div');
            container.innerHTML = personHtml;

            return Array.from(container.querySelectorAll('a[href]'))
                .map(anchor => {
                    const match = (anchor.getAttribute('href') || '').match(/\/pessoas\/([^/]+)\/?/i);
                    return match ? decodeURIComponent(match[1]).toLowerCase() : '';
                })
                .filter(slug => slug !== '');
        },
        extractCastField(groupElement, labelPattern) {
            const fields = Array.from(groupElement.querySelectorAll('.tainacan-metadatum'));
            const field = fields.find(fieldElement => {
                const labelElement = fieldElement.querySelector('.child-metadatum-label');
                return labelElement && labelPattern.test(labelElement.textContent || '');
            });

            const valueElement = field ? field.querySelector('.child-metadatum-value') : null;

            return {
                html: valueElement ? valueElement.innerHTML.trim() : '',
                text: valueElement ? (valueElement.textContent || '').trim() : ''
            };
        },
        getThumbnailSrc(item) {
            if (!item || !item.thumbnail || this.shouldHideItemsThumbnail || !this.$thumbHelper)
                return '';

            return this.$thumbHelper.getSrc(item.thumbnail, 'tainacan-medium', item.document_mimetype);
        },
        stripHtml(html) {
            if (!html)
                return '';

            if (typeof document == 'undefined')
                return html.toString().replace(/<[^>]*>/g, '').trim();

            const temporaryElement = document.createElement('div');
            temporaryElement.innerHTML = html;

            return (temporaryElement.textContent || temporaryElement.innerText || '').trim();
        }
    }
}
</script>
