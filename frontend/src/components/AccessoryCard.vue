<script setup lang="ts">
import { useCartStore } from '@/stores/CartStore.ts';
import { useEquipmentStore, type EquipmentPhoto } from "@/stores/EquipmentStore.ts";
import { Trash2 } from "lucide-vue-next";
import { onMounted, ref } from "vue";

const props = defineProps<{
    id: string
    name: string
    available: boolean
    isAdmin: boolean
}>();

const mainPhoto = ref<EquipmentPhoto | null>(null);
const emit = defineEmits(['remove']);
const cart = useCartStore();
const equipmentStore = useEquipmentStore();

onMounted(async () => {
    mainPhoto.value = await equipmentStore.fetchMainPhoto(props.id);
});
</script>

<template>
    <div class="accessory-card" :class="{ unavailable: !available }">
        <div class="accessory-thumb">
            <img
                v-if="mainPhoto"
                :src="mainPhoto.url"
                :alt="`Hauptbild von ${name}`"
                class="thumb-image"
            />
        </div>

        <div class="accessory-info">
            <h3>{{ name }}</h3>
            <div class="accessory-tags">
                <span class="accessory-tag">{{ available ? 'Verfügbar' : 'Nicht verfügbar' }}</span>
            </div>
        </div>

        <button v-if="isAdmin" class="remove-button" @click="emit('remove', id)"><Trash2 /></button>
        <span v-else class="accessory-actions">
            <button class="accessory-btn" :hidden="!available || cart.has(id)" @click="cart.add(id)">{{ cart.has(id) ? '✓' : '+' }}</button>
            <button class="accessory-btn" :hidden="!available || cart.hasNot(id)" @click="cart.remove(id)">-</button>
        </span>
    </div>
</template>

<style scoped>
.accessory-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    margin-bottom: 14px;
    background: #FFFFFF;
    border: 1px solid #E8EAEE;
    border-radius: 16px;
}

.accessory-thumb {
    flex-shrink: 0;
    width: 72px;
    height: 72px;
    overflow: hidden;
    background: linear-gradient(145deg, #F3F4F6, #E9EDF1);
    border-radius: 12px;
}

.thumb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.accessory-info {
    flex: 1;
    min-width: 0;
}

.accessory-info h3 {
    margin: 0 0 6px;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    color: #111827;
    overflow-wrap: anywhere;
}

.accessory-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.accessory-tag {
    padding: 2px 8px;
    font-size: 12px;
    line-height: 18px;
    color: #374151;
    background: #F3F4F6;
    border-radius: 999px;
}

.accessory-actions {
    display: flex;
    flex-shrink: 0;
    gap: 6px;
}

.accessory-btn {
    flex-shrink: 0;
    min-width: 44px;
    height: 44px;
    padding: 0 12px;
    font-family: inherit;
    font-size: 20px;
    color: #111827;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    cursor: pointer;
}

.accessory-btn:hover {
    background: #F3F4F6;
}

.accessory-btn:focus-visible {
    outline: 2px solid #2456C8;
    outline-offset: 3px;
}

.remove-button svg {
    width: 18px;
    height: 18px;
}

.remove-button {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 43px;
    height: 43px;
    padding: 0;
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border: none;
    border-radius: var(--radius-s);
    cursor: pointer;
    font-size: 14px;
}

.remove-button:hover {
    background: #fbdada;
}

.unavailable {
    opacity: 0.5;
}
</style>
