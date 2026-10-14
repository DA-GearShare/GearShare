import { reactive, ref } from 'vue';
import { defineStore } from 'pinia';

export const useEquipmentStore = defineStore('equipment', () => {
    const equipment = ref([]);
    const filters = reactive({ sort: 'name', order: 'asc', status: '', search: '', page: 1, pageSize: 20 });
    const meta = ref([]);
    async function fetchEquipment(){
        const params = new URLSearchParams();
        for (const [key, value] of Object.entries(filters)){
            params.append(key, String(value));
        }
        const response = await fetch(`/api/v1/equipment?${params}`);
        const data = await response.json();
        equipment.value = data.items;
        meta.value = data.meta;
    }

    return { equipment, meta, fetchEquipment, filters };
});
