import { defineStore } from 'pinia';
import {computed, ref} from "vue";

export const useCompareStore = defineStore('compare', () => {
    const maxEquipments = 10;
    const items = ref<string[]>([]);

    const isFull = computed(() => items.length >= maxEquipments);

    function has(id: string){
        return items.value.includes(id);
    }
    function add(id: string){
        if (!has(id) && !isFull.value){
            items.value.push(id);
        }
    }
    function remove(id: string){
        items.value = items.value.filter(i => i !== id);
    }
    function clear(){
        items.value = [];
    }
    return {items, maxEquipments, isFull, has, add, remove, clear}
})
