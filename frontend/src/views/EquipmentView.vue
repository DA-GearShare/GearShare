<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import EquipmentCard from "@/components/EquipmentCard.vue";
import SearchForm from "@/components/SearchForm.vue";
import FilterDropdown from "@/components/FilterDropdown.vue";

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
</script>

<template>
    <SearchForm @search="handleSearch"/>
    <FilterDropdown @filter="handleFilter"/>
    <EquipmentCard v-for="item in store.equipment" :name="item.name" :model="item.model" :type="item.type" :status="item.status" :key="item.id"/>
</template>
<style scoped>
</style>
