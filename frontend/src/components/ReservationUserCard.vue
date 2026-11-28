<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore';
import { formatRange, formatTime } from '@/utils/dateFormat.ts';
import type { Reservation, ReservationItem } from '@/types/reservation';
import CancelModal from "@/modals/CancelModal.vue";

const props = defineProps<{
    reservation: Reservation;
}>();

const equipmentStore = useEquipmentStore();
const expanded = ref(false);
const equipmentById = ref<Record<string, { name: string; category: string }>>({});
const equipmentPhotos = ref<Record<string, string>>({});
const showCancel = ref(false);

async function loadPhoto(item: ReservationItem) {
    const id = item.requestedEquipmentId;

    if (!id || equipmentPhotos.value[id]) {
        return;
    }

    const photo = await equipmentStore.fetchMainPhoto(id);
    if (photo) {
        equipmentPhotos.value[id] = photo.url;
    }
}

function getPhoto(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return '';
    }
    return equipmentPhotos.value[item.requestedEquipmentId] || '';
}

onMounted(() => {
    for (const item of props.reservation.items.slice(0, 3)) {
        loadPhoto(item);
    }
});

async function toggle() {
    expanded.value = !expanded.value;

    if (!expanded.value) {
        return;
    }

    for (const item of props.reservation.items) {
        const id = item.requestedEquipmentId;

        if (!id || equipmentById.value[id]) {
            continue;
        }

        const equipment = await equipmentStore.fetchEquipmentById(id);
        if (equipment) {
            equipmentById.value[id] = { name: equipment.name, category: equipment.category };
        }

        await loadPhoto(item);
    }
}

function CancelPopUp() {
    showCancel.value = true;
}
function closeCancel() {
    showCancel.value = false;
}
</script>

<template>
    <article class="card reservation">
        <div class="top">
            <div class="date-box">
                <strong>{{ formatRange(reservation.startDatetime, reservation.endDatetime) }}</strong>
                <span class="meta">{{ formatTime(reservation.startDatetime) }}–{{ formatTime(reservation.endDatetime) }}</span>
            </div>

            <div class="thumbs">
                <div v-for="item in reservation.items.slice(0, 3)" :key="item.id" class="thumb-tile">
                    <img v-if="getPhoto(item)" :src="getPhoto(item)" alt="Gerätefoto" class="thumb-image" />
                    <span v-else>IMG</span>
                </div>
            </div>

            <button v-if="reservation.status === 'active'" type="button" class="btn-secondary photo-button" aria-label="Zustandsfotos">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 8h3l2-3h6l2 3h3v11H4z" />
                    <circle cx="12" cy="13" r="3.5" />
                </svg>
            </button>
        </div>
        <div class="bottom">
            <span class="hint">
                <button v-if="reservation.status === 'requested'" @click="CancelPopUp" type="button" class="btn-secondary withdraw">
                    zurückziehen
                </button>
            </span>

            <button type="button" class="toggle" :aria-expanded="expanded" @click="toggle">
                <span>{{ reservation.items.length }} Geräte</span>
                <svg
                    class="chevron-icon"
                    :class="{ open: expanded }"
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <CancelModal
            v-if="showCancel"
            title="Reservierung zurückziehen"
            :id=reservation.id
            @close="closeCancel"
        />

        <ul v-if="expanded" class="item-list">
            <li v-for="item in reservation.items" :key="item.id" class="card-row item-row">
                <div class="thumb thumb-small">
                    <img v-if="getPhoto(item)" :src="getPhoto(item)" alt="Gerätefoto" class="thumb-image" />
                    <span v-else>IMG</span>
                </div>
                <div>
                    <strong>
                        {{ (item.requestedEquipmentId && equipmentById[item.requestedEquipmentId]?.name) || 'Wird geladen…' }}
                    </strong>
                    <div class="meta">
                        {{ item.requestedEquipmentId && equipmentById[item.requestedEquipmentId]?.category }}
                    </div>
                </div>
            </li>
        </ul>
    </article>
</template>

<style scoped>
.reservation {
    padding: var(--space-m);
}

.top {
    display: flex;
    gap: var(--space-s);
}

.date-box {
    display: flex;
    flex-direction: column;
    width: 150px;
    gap: var(--space-xs);
    flex-shrink: 0;
    padding: var(--space-m);
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumbs {
    display: flex;
    flex: 1;
    gap: var(--space-s);
    min-width: 0;
}

.thumb-tile {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    min-width: 0;
    max-width: 100px;
    overflow: hidden;
    font-size: var(--text-help-size);
    color: var(--color-muted);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumb-small {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    overflow: hidden;
    font-size: var(--text-help-size);
    color: var(--color-muted);
}

.photo-button {
    align-self: center;
    min-height: 40px;
    margin-left: auto;
    padding: 0 var(--space-m);
}

.toggle {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    margin: 0 0 0 auto;
    padding: 0;
    font: inherit;
    font-size: var(--text-label-size);
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
    background: none;
    border: none;
    cursor: pointer;
}

.chevron-icon {
    color: var(--color-accent);
    transition: transform 0.15s;
}

.chevron-icon.open {
    transform: rotate(90deg);
}

.item-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    margin: var(--space-m) 0 0;
    padding: var(--space-m) 0 0;
    list-style: none;
    border-top: 1px solid var(--color-border);
}

.bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: var(--space-s);
}

.withdraw {
    min-height: 0;
    padding: 6px var(--space-m);
    font-size: var(--text-label-size);
    line-height: 1.2;
}
</style>
