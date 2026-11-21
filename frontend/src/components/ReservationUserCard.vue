<script setup lang="ts">
import { computed, ref } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore';
import type { Reservation } from '@/types/reservation';

const props = defineProps<{
    reservation: Reservation;
}>();

const equipmentStore = useEquipmentStore();
const expanded = ref(false);
const equipmentById = ref<Record<string, { name: string; category: string }>>({});

async function toggle() {
    expanded.value = !expanded.value;
    if (expanded.value) await loadEquipment();
}

async function loadEquipment() {
    for (const item of props.reservation.items) {
        const id = item.requestedEquipmentId;
        if (!id || equipmentById.value[id]) continue;
        const equipment = await equipmentStore.fetchEquipmentById(id); // <- an deinen Store anpassen
        if (equipment) {
            equipmentById.value[id] = { name: equipment.name, category: equipment.category };
        }
    }
}

const pad = (n: number) => String(n).padStart(2, '0');
const fullDate = (d: Date) => `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
const time = (d: Date) => `${pad(d.getHours())}:${pad(d.getMinutes())}`;

const dateLabel = computed(() => {
    const start = new Date(props.reservation.startDatetime);
    const end = new Date(props.reservation.endDatetime);
    if (start.toDateString() === end.toDateString()) return fullDate(start);
    const sameMonth =
        start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear();
    return sameMonth
        ? `${pad(start.getDate())}.–${fullDate(end)}`
        : `${fullDate(start)}–${fullDate(end)}`;
});

const timeLabel = computed(
    () => `${time(new Date(props.reservation.startDatetime))}–${time(new Date(props.reservation.endDatetime))}`
);

const previewItems = computed(() => props.reservation.items.slice(0, 3));

const countLabel = computed(() => {
    const n = props.reservation.items.length;
    return n === 1 ? '1 Gerät' : `${n} Geräte`;
});
</script>

<template>
    <article class="card reservation">
        <div class="card-row">
            <div class="date-box">
                <strong>{{ dateLabel }}</strong>
                <span class="meta">{{ timeLabel }}</span>
            </div>

            <div class="thumbs">
                <div v-for="item in previewItems" :key="item.id" class="thumb thumb-small">IMG</div>
            </div>

            <button
                v-if="reservation.status === 'active'"
                type="button"
                class="btn-secondary photo-button"
                aria-label="Zustandsfotos"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 8h3l2-3h6l2 3h3v11H4z" />
                    <circle cx="12" cy="13" r="3.5" />
                </svg>
            </button>
        </div>

        <button type="button" class="toggle" :aria-expanded="expanded" @click="toggle">
            <span>{{ countLabel }}</span>
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

        <ul v-if="expanded" class="item-list">
            <li v-for="item in reservation.items" :key="item.id" class="card-row item-row">
                <div class="thumb thumb-small">IMG</div>
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
    padding: var(--space-m) var(--space-l);
}

.date-box {
    display: flex;
    flex-direction: column;
    padding: var(--space-s) var(--space-m);
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumbs {
    display: flex;
    gap: var(--space-s);
}

.thumb-small {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    font-size: var(--text-help-size);
    color: var(--color-muted);
}

.photo-button {
    min-height: 40px;
    margin-left: auto;
    padding: 0 var(--space-m);
}

.toggle {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    margin: var(--space-s) 0 0 auto;
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
</style>
