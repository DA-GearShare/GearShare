
<script setup lang="ts">
import { onMounted } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import { useAuthStore } from '@/stores/AuthStore.ts';
import EquipmentCard from "@/components/EquipmentCard.vue";
import SearchForm from "@/components/SearchForm.vue";
import FilterDropdown from "@/components/FilterDropdown.vue";
import ButtonsPagination from "@/components/ButtonsPagination.vue";

const props = defineProps<{
    pickerMode?: boolean;
    excludeIds?: string[];
}>();

const emit = defineEmits<{
    (e: 'select', id: string): void;
}>();

const authStore = useAuthStore();
const store = useEquipmentStore();

onMounted(() => {
    store.fetchEquipment();
});

const applyFilter = () => {
    store.filters.page = 1;
    store.fetchEquipment();
};

const handleSearch = (search: string) => {
    store.filters.search = search;
    applyFilter();
};

const handleFilter = (
    sort: string,
    order: string,
    from: string,
    to: string
) => {
    store.filters.sort = sort;
    store.filters.order = order;
    store.filters.from = from;
    store.filter.to = to;
    applyFilter();
};

const handlePageChange = (page: number) => {
    store.filters.page = page;
    store.fetchEquipment();
};

const handleSelect = (id: string) => {
    emit('select', id);
};
</script>

<template>
    <div class="page-header" v-if="!pickerMode">
        <h1>Inventar</h1>

        <RouterLink
            v-if="authStore.hasRole(['Admin'])"
            to="/inventar/neu"
            class="btn-primary"
        >
            Equipment hinzufügen
        </RouterLink>
    </div>

    <div class="toolbar">
        <SearchForm @search="handleSearch" />
        <FilterDropdown @filter="handleFilter" />
    </div>

    <div class="card-list">
        <EquipmentCard
            v-for="item in store.equipment"
            :key="item.id"
            :id="item.id"
            :name="item.name"
            :model="item.model"
            :type="item.type"
            :selectable="props.pickerMode"
            :select-disabled="props.excludeIds?.includes(item.id)"
            @select="handleSelect"
        />
    </div>

    <div class="pagination-area">
        <ButtonsPagination
            v-if="store.meta.totalPages"
            :page="store.meta.page"
            :total_pages="store.meta.totalPages"
            @changePage="handlePageChange"
        />
    </div>
</template>

<style scoped>
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-l);
    margin-bottom: var(--space-l);
}

.page-header h1 {
    margin: 0;
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-m);
    margin-bottom: var(--space-l);
}

.toolbar > :first-child {
    flex: 1;
    min-width: 200px;
}

.pagination-area {
    display: flex;
    justify-content: center;
    margin-top: var(--space-xl);
}
</style>
