import { reactive, ref } from 'vue';
import { defineStore } from 'pinia';

export const useEquipmentStore = defineStore('equipment', () => {
    const equipment = ref([]);
    const filters = reactive({ sort: 'name', order: 'asc', from: '', to: '', search: '', page: 1, pageSize: 10 });
    const meta = ref([]);
    async function fetchEquipment(){
        const params = new URLSearchParams();
        for (const [key, value] of Object.entries(filters)){
            if (value === '' || value === null || value === undefined) continue;
            params.append(key, String(value));
        }

        if (!filters.from || !filters.to) {
            params.delete('from');
            params.delete('to');
        }

        const response = await fetch(`/api/v1/equipment?${params}`);
        const data = await response.json();
        equipment.value = data.items;
        meta.value = data.meta;
    }

    async function createEquipment(payload: Record<string, string>){
        try {
            const res = await fetch('/api/v1/equipment', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify( payload ),
            });
            if (!res.ok) throw new Error('Status ' + res.status);
            return await res.json();
        } catch (e) {
            console.error(e);
        }
    }

    return { equipment, meta, fetchEquipment, filters, createEquipment };
});
