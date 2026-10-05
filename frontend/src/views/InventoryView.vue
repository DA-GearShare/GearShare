<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import EquipmentCard from "@/components/EquipmentCard.vue";
import SearchForm from "@/components/SearchForm.vue";

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
</script>

<template>
    <SearchForm @search="handleSearch"/>
    <EquipmentCard v-for="item in filteredItems" :key="item.id" :name="item.name" :model="item.model" :type="item.type" :status="item.status"/>
</template>

<style scoped>

</style>
