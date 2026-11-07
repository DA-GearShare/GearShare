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
        if (!response.ok) return;
        const data = await response.json();
        equipment.value = data.items;
        meta.value = data.meta;
    }

    async function fetchEquipmentById(id: string){
        try {
            const res = await fetch(`/api/v1/equipment/${id}`);
            if (!res.ok) throw new Error('Status ' + res.status);
            return await res.json();
        } catch(e) {
            console.error(e);
            return null;
        }
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

    async function updateEquipment(id: string, payload: Record<string, string>) {
        try {
            const res = await fetch(`/api/v1/equipment/${id}`, {
                method: 'PUT',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('Status ' + res.status);
            return await res.json();
        } catch (e) {
            console.error(e);
            return null;
        }
    }

    async function deleteEquipment(id: string) {
        try {
            const res = await fetch(`/api/v1/equipment/${id}`, {
                method: 'DELETE',
                credentials: 'include',
            });

            if (res.ok) return { ok: true, message: '' };
            if (res.status === 409) return { ok: false, message: 'Das Equipment hat noch offene Reservierungen und kann nicht gelöscht werden.' };
            if (res.status === 403) return { ok: false, message: 'Keine Berechtigung zum Löschen.' };
            if (res.status === 404) return { ok: false, message: 'Das Equipment wurde nicht gefunden.' };
            return { ok: false, message: `Löschen fehlgeschlagen (Status ${res.status}).` };
        } catch (e) {
            console.error(e);
            return { ok: false, message: 'Server nicht erreichbar.' };
        }
    }

    return { equipment, meta, fetchEquipment, filters, createEquipment, updateEquipment, fetchEquipmentById, deleteEquipment };
});
