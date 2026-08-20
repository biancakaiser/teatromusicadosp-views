import PersonViewMode from './src/person-view-mode/person-template.vue';
import CompanyViewMode from './src/company-view-mode/company-template.vue';

window.tainacan_extra_components = typeof window.tainacan_extra_components != "undefined" ? window.tainacan_extra_components : {};
window.tainacan_extra_components["view-mode-person"] = PersonViewMode;
window.tainacan_extra_components["view-mode-company"] = CompanyViewMode;