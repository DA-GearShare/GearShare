<script setup lang="ts">
    import { computed, onMounted, ref } from 'vue';
    import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
    import EquipmentCard from "@/components/EquipmentCard.vue";
    import SearchForm from "@/components/SearchForm.vue";
    import FilterDropdown from "@/components/FilterDropdown.vue";
    import ButtonsPagination from "@/components/ButtonsPagination.vue";

    defineProps<{
        pickerMode?: boolean
        excludeIds?: string[]
    }>();

    const emit = defineEmits(['select']);

    const store = useEquipmentStore();
    onMounted(() => {
        return store.fetchEquipment();
    });
    const applyFilter = () => {
        store.filters.page = 1;
        store.fetchEquipment();
    }
    const handleSearch = (search:string) => {
        store.filters.search = search;
        applyFilter();
    };
    const handleFilter = (sort:string, order:string, status:string) => {
        store.filters.sort= sort;
        store.filters.order = order;
        store.filters.status = status;
        applyFilter();
    }
    const handlePageChange = (page:Number) => {
        store.filters.page = page;
        store.fetchEquipment();
    }
</script>

<template>
    <SearchForm @search="handleSearch"/>
    <FilterDropdown @filter="handleFilter"/>
    <EquipmentCard
        v-for="item in store.equipment"
        :key="item.id"
        :id="item.id"
        :name="item.name"
        :model="item.model"
        :type="item.type"
        :status="item.status"
        :selectable="pickerMode"
        :select-disabled="excludeIds?.includes(item.id)"
        @select="(id: string) => emit('select', id)"
    />
    <ButtonsPagination v-if="store.meta.totalPages" :page="store.meta.page" :totalPages="store.meta.totalPages" @changePage="handlePageChange" />
</template>
<style scoped>
</style>
