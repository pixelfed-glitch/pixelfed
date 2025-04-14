<template>
    <div class="pb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
            <div class="title">
                <h3 class="font-weight-bold mb-0">
                    Filters
                </h3>
                <p class="lead mb-3 mb-md-0">Manage your custom filters.</p>
            </div>
            <button
                @click="showAddFilterModal = true"
                class="btn btn-primary font-weight-bold rounded-pill px-3"
                :disabled="filters?.length >= 20">
                <i class="fas fa-plus mr-1"></i> Add New Filter
            </button>
        </div>

        <!-- <p>Customize your experience with powerful content filters that screen for specific words or phrases throughout your entire account—including home and public timelines, notifications, messages, groups, hashtag feeds, and explore sections.</p> -->
        <p>Customize your experience with powerful content filters that screen for specific words or phrases throughout your entire account - including home and public timelines, and hashtag feeds.</p>
        <p class="text-muted mb-0">You can add up to <strong>20 filters</strong> that can have up to <strong>10 keywords</strong>.</p>
        <p class="text-muted mb-4 small">Learn more in our <a href="/site/help">Help Center</a>.</p>

        <div v-if="loading" class="d-flex justify-content-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <div v-else-if="filters.length === 0" class="bg-light p-4 rounded text-center border">
            <div class="py-3">
                <i class="fas fa-filter text-secondary fa-3x mb-3"></i>
                <p class="font-weight-bold text-secondary">You don't have any content filters yet.</p>
                <p class="text-muted small mt-2">
                    Filters help you hide content containing specific words or phrases from your timelines.
                </p>
                <button @click="showAddFilterModal = true" class="btn btn-outline-primary rounded-pill font-weight-light mt-2">
                    <i class="fas fa-plus mr-1"></i> Create Your First Filter
                </button>
            </div>
        </div>

        <div v-else>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p v-if="!searchQuery || !searchQuery.trim().length" class="text-muted mb-0">
                    <span class="font-weight-bold">{{ filters.length }}</span>
                    {{ filters.length === 1 ? 'filter' : 'filters' }} found
                </p>
                <p v-else class="text-muted mb-0">
                    <span class="font-weight-bold">{{ filteredFilters.length }}</span>
                    {{ filteredFilters.length === 1 ? 'filter' : 'filters' }} found
                </p>
                <div class="input-group input-group-sm" style="max-width: 250px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0">
                          <i class="fas fa-search text-muted"></i>
                      </span>
                  </div>
                  <input
                      type="text"
                      v-model="searchQuery"
                      class="form-control border-left-0 bg-light"
                      placeholder="Search filters..."
                      />
                  </div>
            </div>

            <div v-if="searchQuery && filteredFilters.length === 0" class="bg-light p-4 rounded text-center border">
                <div class="py-3">
                    <i class="fas fa-filter text-secondary fa-3x mb-3"></i>
                    <p class="lead text-secondary">You don't have any content filters that match <strong>{{searchQuery}}</strong>.</p>
                    <p class="text-muted small mt-2">
                    Filters help you hide content containing specific words or phrases from your timelines.
                    </p>
                    <button @click="showAddFilterModal = true" class="btn btn-outline-primary rounded-pill font-weight-light mt-2">
                        <i class="fas fa-plus mr-1"></i> Create new Filter
                    </button>
                </div>
            </div>

            <div class="card-deck-wrapper">
                <div class="list-group">
                    <filter-card
                    v-for="filter in filteredFilters"
                    :key="filter.id"
                    :filter="filter"
                    @edit="editFilter"
                    @delete="deleteFilter"
                    />
                </div>
            </div>
        </div>

        <filter-modal
            v-if="showAddFilterModal || showEditFilterModal"
            :filter="editingFilter"
            :is-editing="showEditFilterModal"
            :wizard-mode="wizardMode"
            @delete="handleFilterDelete"
            @toggle="updateWizardMode"
            @close="closeModals"
            @save="saveFilter"
        />
    </div>
</template>

<script>
import FilterCard from './FilterCard.vue';
import FilterModal from './FilterModal.vue';

export default {
    name: 'FiltersList',
    components: {
        FilterCard,
        FilterModal
    },
    data() {
        return {
            filters: [],
            loading: true,
            filtersLoaded: false,
            showAddFilterModal: false,
            showEditFilterModal: false,
            editingFilter: null,
            searchQuery: '',
            wizardMode: true,
        }
    },
    computed: {
        filteredFilters() {
            if (!this.searchQuery) return this.filters;

            const query = this.searchQuery.toLowerCase().trim();
            return this.filters.filter(filter => {
                if (filter.title && filter.title.toLowerCase().includes(query)) return true;

                if (filter.keywords && filter.keywords.some(k =>
                    k.keyword && k.keyword.toLowerCase().includes(query)
                    )) return true;

                    if (filter.context && filter.context.some(c => c.toLowerCase().includes(query))) return true;

                return false;
            });
        }
    },
    mounted() {
        this.fetchFilters();
    },
    methods: {
        fetchFilters() {
            this.loading = true;
            axios.get('/api/v2/filters')
            .then(response => {
                this.filters = response.data;
            })
            .catch(error => {
                console.error('Failed to fetch filters:', error);
                swal('Error', 'Failed to load filters. Please try again.', 'error');
            })
            .finally(() => {
                this.loading = false;
                this.filtersLoaded = true;
            });
        },
        closeModals() {
            this.wizardMode = true;
            this.showAddFilterModal = false;
            this.showEditFilterModal = false;
            this.editingFilter = null;
        },
        handleFilterDelete() {
            this.deleteFilter(this.editingFilter.id);
            this.closeModals();
        },
        updateWizardMode() {
            this.wizardMode = !this.wizardMode;
        },
        editFilter(filter) {
            this.wizardMode = false;
            this.editingFilter = JSON.parse(JSON.stringify(filter));
            this.showEditFilterModal = true;
        },
        deleteFilter(filterId) {
            if (!confirm('Are you sure you want to delete this filter?')) return;

            this.loading = true;
            axios.delete(`/api/v2/filters/${filterId}`)
            .then(() => {
                this.filters = this.filters.filter(f => f.id !== filterId);
                swal('Success', 'Filter deleted successfully', 'success');
            })
            .catch(error => {
                swal('Error', 'Failed to delete filter. Please try again.', 'error')
            })
            .finally(() => {
                this.loading = false;
            });
        },
        saveFilter(filterData) {
            this.loading = true;

            if (this.showEditFilterModal) {
                axios.put(`/api/v2/filters/${filterData.id}`, filterData)
                .then(response => {
                    const updatedIndex = this.filters.findIndex(f => f.id === filterData.id);
                    if (updatedIndex !== -1) {
                        this.$set(this.filters, updatedIndex, response.data);
                    }
                    this.$bvToast.toast(`${response.data?.title ?? 'Untitled'} filter updated successfully`, {
                        title: 'Updated Filter',
                        autoHideDelay: 5000,
                        appendToast: true,
                        variant: 'success'
                    })
                    this.closeModals();
                })
                .catch(error => {
                    if(error.response?.data?.error) {
                        swal(error.response?.data?.error, error.response?.data?.message, 'error')
                    } else if(error.response?.data?.message) {
                        swal('Error', error.response?.data?.message, 'error')
                    } else {
                        swal('Error', 'Failed to update filter. Please try again.', 'error')
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
            } else {
                axios.post('/api/v2/filters', filterData)
                .then(response => {
                    this.filters.unshift(response.data);
                    this.$bvToast.toast(`${response.data?.title ?? 'Untitled'} filter created`, {
                        title: 'New Filter',
                        autoHideDelay: 5000,
                        appendToast: true,
                        variant: 'success'
                    })
                    this.closeModals();
                })
                .catch(error => {
                    if(error.response?.data?.error) {
                        swal(error.response?.data?.error, error.response?.data?.message, 'error')
                    } else if(error.response?.data?.message) {
                        swal('Error', error.response?.data?.message, 'error')
                    } else {
                        swal('Error', 'Failed to create filter. Please try again.', 'error')
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
            }
        }
    }
}
</script>

<style scoped>
    .card-deck-wrapper {
        overflow-y: auto;
        max-height: 40dvh;
    }
</style>
