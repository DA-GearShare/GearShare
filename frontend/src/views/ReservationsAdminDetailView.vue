<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ChevronLeft, Pencil } from 'lucide-vue-next';
import { useReservationStore } from '@/stores/ReservationStore.ts';
import { useUsersStore } from '@/stores/UsersStore.ts';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import { formatDayMonth, formatDayMonthTime, formatRange, formatTime } from '@/utils/dateFormat.ts';
import type { Reservation, ReservationItem } from '@/types/reservation';

const route = useRoute();
const reservationStore = useReservationStore();
const usersStore = useUsersStore();
const equipmentStore = useEquipmentStore();

const id = computed(() => route.params.id as string);
const reservations = computed<Reservation[]>(() => reservationStore.reservations ?? []);
const selected = computed(() => reservations.value.find(r => r.id === id.value));

onMounted(() => {
    reservationStore.fetchReservations();
    usersStore.fetchUsers();
});

function personName(reservation: Reservation) {
    const user = usersStore.users.find(u => u.id === reservation.requestedByUserId);
    return user?.name ?? user?.email ?? '…';
}

const equipmentNames = ref<Record<string, string>>({});
const equipmentPhotos = ref<Record<string, string>>({});

async function loadDevices() {
    if (!selected.value) {
        return;
    }

    for (const item of selected.value.items) {
        const equipmentId = item.requestedEquipmentId;

        if (!equipmentId) {
            continue;
        }
        if (equipmentNames.value[equipmentId]) {
            continue;
        }

        const equipment = await equipmentStore.fetchEquipmentById(equipmentId);
        if (equipment) {
            equipmentNames.value[equipmentId] = equipment.name;
        }

        const photo = await equipmentStore.fetchMainPhoto(equipmentId);
        if (photo) {
            equipmentPhotos.value[equipmentId] = photo.url;
        }
    }
}

watch(selected, loadDevices, { immediate: true });

function getName(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return 'Unbekanntes Gerät';
    }
    return equipmentNames.value[item.requestedEquipmentId] || 'Wird geladen…';
}

function getPhoto(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return '';
    }
    return equipmentPhotos.value[item.requestedEquipmentId] || '';
}
</script>
<template>
    <RouterLink to="/admin/reservierungen" class="back-link">
        <ChevronLeft :size="16" /> Zur Übersicht
    </RouterLink>

    <p class="eyebrow">Verwaltung</p>
    <h1>Admin-Reservierungen</h1>
    <p class="meta">Person auswählen und Reservierungszeiten einsehen.</p>

    <p v-if="reservationStore.error" class="meta">{{ reservationStore.error }}</p>

    <div class="layout">
        <aside class="persons">
            <h2 class="persons-title">Personen</h2>

            <RouterLink
                v-for="reservation in reservations"
                :key="reservation.id"
                :to="`/admin/reservierungen/${reservation.id}`"
                class="person-card"
                :class="{ active: reservation.id === id }"
            >
                <div class="person-thumb">IMG</div>
                <strong>{{ personName(reservation) }}</strong>
                <span class="hint">{{ reservation.items.length }} Geräte</span>
                <span class="hint">{{ formatDayMonth(reservation.startDatetime) }}–{{ formatDayMonth(reservation.endDatetime) }}</span>
            </RouterLink>
        </aside>
        <section v-if="selected" class="card detail">
            <div>
                <h2 class="detail-name">{{ personName(selected) }}</h2>
                <span class="hint">R-{{ selected.id.slice(-4).toUpperCase() }}</span>
            </div>

            <div class="time-box">
                <div class="time-box-head">
                    <strong>Von – Bis</strong>
                    <button type="button" class="icon-button" aria-label="Zeitraum bearbeiten" title="Zeitraum bearbeiten">
                        <Pencil />
                    </button>
                </div>
                <strong class="time-range">{{ formatRange(selected.startDatetime, selected.endDatetime) }}</strong>
                <span class="meta">{{ formatTime(selected.startDatetime) }}–{{ formatTime(selected.endDatetime) }}</span>
            </div>

            <div class="devices-head">
                <h3>Geräte</h3>
                <span class="hint">{{ selected.items.length }} Geräte</span>
            </div>

            <div class="device-grid">
                <article v-for="item in selected.items" :key="item.id" class="device-card">
                    <div class="device-thumb">
                        <img
                            v-if="getPhoto(item)"
                            :src="getPhoto(item)"
                            alt="Gerätefoto"
                            class="device-image"
                        />
                        <span v-else>IMG</span>
                    </div>
                    <strong>{{ getName(item) }}</strong>
                    <span class="label">Abholung</span>
                    <span class="hint">{{ formatDayMonthTime(selected.startDatetime) }}</span>
                    <span class="label">Rückgabe</span>
                    <span class="hint">{{ formatDayMonthTime(selected.endDatetime) }}</span>
                </article>
            </div>
        </section>

        <p v-else class="meta">Reservierung wird geladen oder wurde nicht gefunden.</p>
    </div>
</template>

<style scoped>
.back-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    margin-bottom: var(--space-m);
    font-size: var(--text-label-size);
    font-weight: var(--font-bold);
    text-decoration: none;
}

.layout {
    display: grid;
    grid-template-columns: 110px 1fr;
    gap: var(--space-s);
    align-items: start;
    margin-top: var(--space-l);
}

.persons {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}

.persons-title {
    margin: 0;
    font-size: var(--text-label-size);
}

.person-card {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: var(--space-s);
    color: var(--color-text-primary);
    text-decoration: none;
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-tab);
    box-shadow: var(--shadow-card);
}

.person-card.active {
    background: var(--color-info);
    border-color: var(--color-accent);
}

.person-thumb,
.device-thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    font-size: var(--text-help-size);
    color: var(--color-muted);
    background: var(--color-soft-surface);
    border-radius: var(--radius-s);
}

.person-thumb {
    width: 44px;
    height: 44px;
    margin-bottom: var(--space-xs);
}

.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    min-width: 0;
}

.detail-name {
    margin: 0;
}

.time-box {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
    padding: var(--space-m);
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.time-box-head,
.devices-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.devices-head h3 {
    margin: 0;
}

.icon-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    color: var(--color-accent);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-s);
    cursor: pointer;
}

.icon-button svg {
    width: 16px;
    height: 16px;
}

.device-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-s);
}

.device-card {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    padding: var(--space-s);
    color: var(--color-text-primary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-tab);
}

.device-thumb {
    aspect-ratio: 4 / 3;
    margin-bottom: var(--space-xs);
}

.device-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.label {
    margin-top: var(--space-xs);
    font-size: var(--text-help-size);
    font-weight: var(--font-bold);
}
</style>
