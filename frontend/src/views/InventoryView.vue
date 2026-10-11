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
const searchTerm = ref('');
const filteredItems = computed(()=>{
    return store.equipment.filter((item) => {
        return item.name.includes(searchTerm.value);
    });
});
const handleSearch = (search:string) => {
    searchTerm.value = search;
};
const handleFilter = (sort:string, order:string) => {
    store.filters.sort= sort;
    store.filters.order = order;
    store.filters.page = 1;
    store.fetchEquipment();
}
</script>

<template>
    <SearchForm @search="handleSearch"/>
    <FilterDropdown @filter=""/>
    <EquipmentCard v-for="item in filteredItems" :key="item.id" :name="item.name" :model="item.model" :type="item.type" :status="item.status"/>
</template>

<style scoped>

</style>
