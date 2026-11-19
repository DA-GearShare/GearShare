<script setup lang="ts">
import { useRoute } from "vue-router";
import { computed, ref, watch } from "vue";
import AccessoryCard from "@/components/AccessoryCard.vue";
import { useAuthStore } from "@/stores/AuthStore.ts";
import EquipmentAll from "@/components/EquipmentAll.vue";
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import EquipmentPhotos from "@/components/EquipmentPhotos.vue";
import { useCartStore } from "@/stores/CartStore.ts";

const cart = useCartStore();

interface Accessory {
    accessoryEquipmentId: string
    name: string
    availableInRequestedPeriod: boolean
}
const route = useRoute();
const autStore = useAuthStore();
const equipmentStore = useEquipmentStore();
const accessories = ref<Accessory[]>([]);
const message = ref();

async function loadAccessories() {
    let url = `/api/v1/equipment/${route.params.id}/accessories`;

    const { from, to } = equipmentStore.filters;
    if (from && to) {
        url += `?from=${from}&to=${to}`;
    }

    const res = await fetch(url);
    if (!res.ok) return;
    accessories.value = await res.json();
}

watch(
    () => {
        return [equipmentStore.filters.from, equipmentStore.filters.to];
    },
    loadAccessories,
    { immediate: true }
);

//Für Admins
const isAdmin = computed(() => {
    return autStore.hasRole(['Admin']);
});
const showPicker = ref(false);
const error = ref('');

const excludeIds = computed(() => {
    return [
        route.params.id as string,
        ...accessories.value.map(a => {
            return a.accessoryEquipmentId;
        }),
    ];
});

async function addAccessory(accessoryId: string) {
    error.value = '';
    const res = await fetch(`/api/v1/equipment/${route.params.id}/accessories`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accessoryEquipmentId: accessoryId }),
    });
    if (!res.ok){
        error.value = await res.text();
        return;
    }
    await loadAccessories();
}
async function removeAccessory(accessoryId: string) {
    error.value = '';
    const res = await fetch(`/api/v1/equipment/${route.params.id}/accessories/${accessoryId}`, {
        method: 'DELETE',
    });

    if (!res.ok) {
        error.value = await res.text();
        return;
    }
    await loadAccessories();
}


function formatDate(value: string) {
    return new Date(value).toLocaleString('de-AT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function addToCart() {
    const id = route.params.id as string;
    if (cart.has(id)) {
        message.value = 'Das Equipment ist bereits im Warenkorb.';
        return;
    }
    cart.add(id);
    message.value = 'Equipment erfolgreich zum Warenkorb hinzugefügt.';
}
</script>

<template>

    <EquipmentPhotos />
    <h2>Empfehlungen</h2>

    <p v-if="equipmentStore.filters.from && equipmentStore.filters.to">
        Verfügbarkeit für {{ formatDate(equipmentStore.filters.from) }} bis {{ formatDate(equipmentStore.filters.to) }}
    </p>

    <AccessoryCard
        v-for="item in accessories"
        :key="item.accessoryEquipmentId"
        :id="item.accessoryEquipmentId"
        :name="item.name"
        :available="item.availableInRequestedPeriod"
        :isAdmin="isAdmin"
        @remove="removeAccessory"
    />

    <button v-if="isAdmin" @click="showPicker = true">Accessory hinzufügen</button>
    <p v-if="error">{{ error }}</p>

    <div v-if="showPicker" class="overlay" @click.self="showPicker = false">
        <div class="popup">
            <button @click="showPicker = false">Schließen</button>
            <EquipmentAll
                picker-mode
                :hide-id="String(route.params.id)"
                :exclude-ids="excludeIds"
                @select="addAccessory"
            />
        </div>
    </div>
    <button class="btn-secondary" @click="addToCart">Zum Warenkorb hinzufügen</button>
    <p v-if="message" class="message-info">{{ message }}</p>
</template>

<style scoped>
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.popup {
    background: white;
    width: 90%;
    max-width: 800px;
    max-height: 85vh;
    overflow-y: auto;
    padding: 20px;
    border-radius: 12px;
}
</style>
