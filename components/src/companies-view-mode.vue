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

                                <span
                                    class="professional-timeline__marker"
                                    aria-hidden="true" />

                                <div class="professional-timeline__content">
                                    <div class="professional-timeline__meta">
                                        <span class="professional-timeline__dates">{{ record.dateRangeLabel || 'Data não informada' }}</span>
                                        <button
                                            v-if="isSlideshowViewModeEnabled"
                                            type="button"
                                            class="professional-timeline__slideshow-trigger"
                                            :aria-label="$i18n.get('label_see_on_fullscreen')"
                                            @click="starSlideshowFromHere(getItemGlobalIndex(record.item, record.index))">
                                            <i
                                                class="tainacan-icon tainacan-icon-viewgallery tainacan-icon-1-125em"
                                                aria-hidden="true" />
                                        </button>
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
    name: 'CompanyViewMode',
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
        otherMetadataColumns() {
            return (this.displayedMetadata || []).filter(column => column && column !== this.companyColumn);
        },
        dateColumns() {
            return this.otherMetadataColumns.filter(column => column.metadata_type_object && column.metadata_type_object.primitive_type == 'date');
        },
        initialDateColumn() {
            return this.findColumnByPattern(this.dateColumns, /inici/i) || this.dateColumns[0] || false;
        },
        finalDateColumn() {
            return this.findColumnByPattern(this.dateColumns, /final|term|fim/i) || this.dateColumns[1] || this.dateColumns[0] || false;
        },
        castColumn() {
            const compoundColumns = this.otherMetadataColumns.filter(column => column.metadata_type_object && column.metadata_type_object.primitive_type == 'compound');
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

                if (!groupsByKey[key]) {
                    groupsByKey[key] = {
                        key: key,
                        label: this.getCompanyGroupLabel(item),
                        link: this.getCompanyGroupLink(item),
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
                const records = group.items.map((item, index) => ({
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
            console.log('toggleCompanyGroup', groupKey, this.activeCompanyGroupKey);
            this.activeCompanyGroupKey = this.activeCompanyGroupKey == groupKey ? null : groupKey;
        },
        getAccordionHeaderId(groupKey) {
            return (this.containerId || 'professional-history') + '-company-header-' + groupKey;
        },
        getAccordionPanelId(groupKey) {
            console.log('getAccordionPanelId', groupKey, this.containerId);
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
        getCompanyGroupLabel(item) {
            const metadata = this.getCompanyMetadata(item);

            if (!metadata)
                return 'Sem companhia';

            if (metadata.value_as_string && metadata.value_as_string.toString().trim() != '')
                return metadata.value_as_string.toString().trim();

            const htmlText = this.stripHtml(metadata.value_as_html);

            return htmlText != '' ? htmlText : 'Sem companhia';
        },
        getCompanyGroupLink(item) {
            const metadata = this.getCompanyMetadata(item);

            if (!metadata)
                return 'Sem companhia';

            if (metadata.value_as_html && metadata.value_as_html != '')
                return metadata.value_as_html;
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
        getFormattedDate(item, column) {
            const metadata = this.getColumnMetadata(item, column);
            if (!metadata)
                return '';

            // Date metadata already arrives localized from the API (date_i18n / value_as_string),
            // so there is no need for a client-side date formatting library.
            if (metadata.date_i18n)
                return metadata.date_i18n;

            if (metadata.value_as_string)
                return metadata.value_as_string;

            return this.stripHtml(metadata.value_as_html);
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
            const start = this.getFormattedDate(item, this.initialDateColumn);
            const end = this.getFormattedDate(item, this.finalDateColumn);

            if (start && end && start != end)
                return start + ' – ' + end;

            return start || end || '';
        },
        getGroupDateRangeLabel(items) {
            const datedValues = [];

            (items || []).forEach(item => {
                [this.initialDateColumn, this.finalDateColumn].forEach(column => {
                    const timestamp = this.getSortableDateTimestamp(item, column);
                    const label = this.getFormattedDate(item, column);
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

<style scoped>
.professional-history-wrapper {
    --ph-color-bg: #FBF6EC;
    --ph-color-surface: #F5EEDF;
    --ph-color-primary: #6B1626;
    --ph-color-primary-strong: #4E0F1B;
    --ph-color-accent: #A9863F;
    --ph-color-text: #372417;
    --ph-color-text-muted: #6B5A4C;
    --ph-color-border: rgba(55, 36, 23, 0.16);
    --ph-radius: 2px;
    --ph-font-serif: Georgia, 'Iowan Old Style', 'Palatino Linotype', 'Times New Roman', serif;
    --ph-font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;

    color: var(--ph-color-text);
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.professional-history {
    background-color: var(--ph-color-bg);
    font-family: var(--ph-font-sans);
    padding: 2rem clamp(1rem, 4vw, 2.5rem);
}

.professional-history__empty {
    padding: 2rem;
    text-align: center;
}

/* Header */
.professional-history__header {
    max-width: 46rem;
    margin: 0 0 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--ph-color-border);
}

.professional-history__eyebrow {
    font-family: var(--ph-font-sans);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ph-color-accent);
    margin: 0 0 0.5rem;
}

.professional-history__title {
    font-family: var(--ph-font-serif);
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 400;
    color: var(--ph-color-primary-strong);
    margin: 0 0 0.6rem;
}

.professional-history__description {
    font-size: 0.95rem;
    color: var(--ph-color-text-muted);
    margin: 0 0 0.75rem;
    line-height: 1.5;
}

.professional-history__summary {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ph-color-primary);
    margin: 0;
}

/* Accordion */
.company-accordion__item {
    border-bottom: 1px solid var(--ph-color-border);
}

.company-accordion__item:first-child {
    border-top: 1px solid var(--ph-color-border);
}

.company-accordion__heading {
    margin: 0;
}

.company-accordion__trigger {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    background: none;
    border: 0;
    cursor: pointer;
    text-align: start;
    padding: 1.1rem 0.25rem;
    font-family: inherit;
    color: inherit;
}

.company-accordion__trigger:hover .company-accordion__title {
    color: var(--ph-color-primary);
}

.company-accordion__trigger:focus-visible {
    outline: 2px solid var(--ph-color-accent);
    outline-offset: 2px;
}

.company-accordion__title {
    font-family: var(--ph-font-serif);
    font-size: 1.15rem;
    color: var(--ph-color-primary-strong);
    flex: 1 1 auto;
    min-width: 0;
}

.company-accordion__summary {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    font-size: 0.75rem;
    color: var(--ph-color-text-muted);
    flex: 0 0 auto;
    text-align: end;
}

.company-accordion__count {
    font-weight: 600;
    color: var(--ph-color-accent);
}

.company-accordion__icon {
    position: relative;
    flex: 0 0 auto;
    width: 0.85rem;
    height: 0.85rem;
}

.company-accordion__icon::before,
.company-accordion__icon::after {
    content: '';
    position: absolute;
    background-color: var(--ph-color-primary);
    transition: transform 0.15s ease;
}

.company-accordion__icon::before {
    top: 50%;
    left: 0;
    width: 100%;
    height: 1px;
    transform: translateY(-50%);
}

.company-accordion__icon::after {
    left: 50%;
    top: 0;
    width: 1px;
    height: 100%;
    transform: translateX(-50%);
}

.company-accordion__trigger[aria-expanded="true"] .company-accordion__icon::after {
    transform: translateX(-50%) rotate(90deg);
    opacity: 0;
}

.company-accordion__panel {
    padding: 0 0.25rem 1.5rem;
}

/*
 * Belt-and-suspenders with v-show: hides the panel purely off the
 * accordion trigger's aria-expanded state, so the collapse still works
 * even if something outside this component ends up beating v-show's
 * inline display style.
 */
.company-accordion__heading:has(.company-accordion__trigger[aria-expanded="false"]) + .company-accordion__panel {
    display: none !important;
}

/* Timeline */
.professional-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
    border-inline-start: 1px solid var(--ph-color-border);
}

.professional-timeline__item {
    position: relative;
    display: flex;
    gap: 0.9rem;
    padding: 0 0 1.5rem 1.25rem;
}

.professional-timeline__item:last-child {
    padding-bottom: 0;
}

.professional-timeline__marker {
    position: absolute;
    left: -4.5px;
    top: 0.3rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--ph-color-accent);
}

.professional-timeline__content {
    flex: 1 1 auto;
    min-width: 0;
}

.professional-timeline__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
}

.professional-timeline__dates {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--ph-color-accent);
}

.professional-timeline__slideshow-trigger {
    background: none;
    border: 0;
    padding: 0.15rem;
    line-height: 1;
    color: var(--ph-color-text-muted);
    cursor: pointer;
}

.professional-timeline__slideshow-trigger:hover {
    color: var(--ph-color-primary);
}

.professional-timeline__body {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.professional-timeline__thumbnail {
    flex: 0 0 auto;
    width: 4.5rem;
    height: 4.5rem;
    object-fit: cover;
    border-radius: var(--ph-radius);
    border: 1px solid var(--ph-color-border);
    background-color: var(--ph-color-surface);
}

.professional-timeline__info {
    min-width: 0;
    flex: 1 1 auto;
}

.professional-timeline__title {
    display: inline-block;
    font-family: var(--ph-font-serif);
    font-size: 1.05rem;
    color: var(--ph-color-text);
    text-decoration: none;
    margin-bottom: 0.4rem;
}

.professional-timeline__title:hover {
    color: var(--ph-color-primary);
    text-decoration: underline;
}

/* Cast list */
.cast-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem 1rem;
}

.cast-list__item {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
    font-size: 0.85rem;
}

.cast-list__person {
    color: var(--ph-color-text);
    font-weight: 600;
}

.cast-list__person :deep(a) {
    color: inherit;
    text-decoration: none;
    border-bottom: 1px solid var(--ph-color-accent);
}

.cast-list__person--placeholder {
    font-style: italic;
    font-weight: 400;
    color: var(--ph-color-text-muted);
}

.cast-list__role {
    color: var(--ph-color-text-muted);
    font-size: 0.78rem;
}

.cast-list__role::before {
    content: '·';
    margin-inline-end: 0.35rem;
    color: var(--ph-color-border);
}

.cast-list__empty {
    font-size: 0.8rem;
    font-style: italic;
    color: var(--ph-color-text-muted);
    margin: 0;
}

/* Skeleton loading */
.skeleton-bar {
    background-color: var(--ph-color-surface);
    border-radius: var(--ph-radius);
    animation: professional-history-pulse 1.2s ease-in-out infinite;
}

.skeleton-bar--eyebrow {
    width: 6rem;
    height: 0.7rem;
    margin-bottom: 0.6rem;
}

.skeleton-bar--title {
    width: 60%;
    max-width: 20rem;
    height: 1.5rem;
}

.company-accordion__item--skeleton {
    padding: 1.1rem 0.25rem;
}

.skeleton-bar--trigger {
    width: 100%;
    height: 1.5rem;
}

@keyframes professional-history-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.45; }
}

@media (prefers-reduced-motion: reduce) {
    .company-accordion__icon::before,
    .company-accordion__icon::after {
        transition: none;
    }

    .skeleton-bar {
        animation: none;
    }
}

/* Responsive */
@media (max-width: 640px) {
    .company-accordion__trigger {
        flex-wrap: wrap;
    }

    .company-accordion__summary {
        align-items: flex-start;
        text-align: start;
        flex-basis: 100%;
    }

    .professional-timeline__body {
        flex-direction: column;
    }

    .professional-timeline__thumbnail {
        width: 100%;
        height: auto;
        max-height: 12rem;
    }
}
</style>
