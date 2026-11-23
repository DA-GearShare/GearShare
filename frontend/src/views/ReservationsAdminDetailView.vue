<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ChevronLeft, Pencil } from 'lucide-vue-next';
import { useReservationStore } from '@/stores/ReservationStore.ts';
import { useUsersStore } from '@/stores/UsersStore.ts';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import { formatDayMonth, formatDayMonthTime, formatRange, formatTime } from '@/utils/dateFormat.ts';
import type { Reservation, ReservationItem } from '@/types/reservation';
import ScanModal from '@/components/ScanModal.vue';

const route = useRoute();
const reservationStore = useReservationStore();
const usersStore = useUsersStore();
const equipmentStore = useEquipmentStore();

const id = computed(() => {
    return route.params.id as string;
});
const reservations = computed<Reservation[]>(() => {
    return reservationStore.reservations ?? [];
});
const selected = computed(() => {
    return reservations.value.find(r => {
        return r.id === id.value;
    });
});

onMounted(() => {
    reservationStore.fetchReservations();
    usersStore.fetchUsers();
});

function personName(reservation: Reservation) {
    const user = usersStore.users.find(u => {
        return u.id === reservation.requestedByUserId;
    });
    return user?.name ?? user?.email ?? '…';
}

const equipmentNames = ref<Record<string, string>>({});
const equipmentPhotos = ref<Record<string, string>>({});
const equipmentCodes = ref<Record<string, string>>({});

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
            if (equipment.code) {
                equipmentCodes.value[equipmentId] = equipment.code;
            }
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

function getCode(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return '';
    }
    return equipmentCodes.value[item.requestedEquipmentId] || '';
}

function getPhoto(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return '';
    }
    return equipmentPhotos.value[item.requestedEquipmentId] || '';
}

//Scan Logik

const showScanner = ref(false);
const scanItemTarget = ref<ReservationItem | null>(null);
const pendingCode = ref('');
const pendingEquipment = ref<{ name?: string } | null>(null);
const replaceItemId = ref('');

const replaceable = computed(() => {
    const items = selected.value?.items || [];
    return items.filter((item) => {
        return item.status === 'requested';
    });
});

function statusLabel(status: string) {
    if (status === 'requested') return 'Angefragt';
    if (status === 'active') return 'Aktiv';
    if (status === 'issued') return 'Ausgegeben';
    if (status === 'returned') return 'Zurückgegeben';
    if (status === 'denied') return 'Abgelehnt';
    if (status === 'cancelled') return 'Storniert';
    return status;
}

const canIssue = computed(() => {
    const status = selected.value?.status;
    return status === 'requested' || status === 'active';
});

function replaceReservation(updated: Reservation) {
    const all: Reservation[] = reservationStore.reservations || [];
    const index = all.findIndex((r) => {
        return r.id === updated.id;
    });

    if (index !== -1) {
        all[index] = updated;
    }
}

function scanGlobal() {
    scanItemTarget.value = null;
    showScanner.value = true;
}

function scanItem(item: ReservationItem) {
    scanItemTarget.value = item;
    showScanner.value = true;
}

function closeScan() {
    showScanner.value = false;
}

async function handleScanned(code: string) {
    showScanner.value = false;

    if (scanItemTarget.value) {
        await issueItem(scanItemTarget.value, code);
    } else {
        await issueGlobal(code);
    }
}

async function issueItem(item: ReservationItem, code: string) {
    if (!selected.value) {
        return;
    }

    const updated = await reservationStore.fetchIssueItem(selected.value.id, item.id, { code });
    if (updated) {
        replaceReservation(updated);
    }
}

async function issueGlobal(code: string) {
    if (!selected.value) {
        return;
    }

    const result = await reservationStore.fetchIssueGlobal(selected.value.id, {
        code,
        confirmAdd: false,
    });
    if (!result) {
        return;
    }

    if (result.conflict) {
        pendingCode.value = code;
        pendingEquipment.value = result.equipment;
        replaceItemId.value = '';
        return;
    }

    replaceReservation(result);
}

async function confirmAdd() {
    if (!selected.value) {
        return;
    }

    const payload: Record<string, unknown> = {
        code: pendingCode.value,
        confirmAdd: true,
    };

    if (replaceItemId.value) {
        payload.replaceItemId = replaceItemId.value;
    }

    const updated = await reservationStore.fetchIssueGlobal(selected.value.id, payload);
    if (updated) {
        replaceReservation(updated);
    }
    cancelAdd();
}

function cancelAdd() {
    pendingCode.value = '';
    pendingEquipment.value = null;
    replaceItemId.value = '';
}
</script>
<template>
    <RouterLink to="/admin/reservierungen" class="back-link">
        <ChevronLeft :size="16" /> Zur Übersicht
    </RouterLink>

    <p class="eyebrow">Verwaltung</p>
    <h1>Admin-Reservierungen</h1>

    <p v-if="reservationStore.error" class="error-text">{{ reservationStore.error }}</p>

    <div class="layout">
        <section v-if="selected" class="card detail">
            <div>
                <h2 class="detail-name">{{ personName(selected) }}</h2>
                <span class="hint">{{ statusLabel(selected.status) }}</span>
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
                <div class="devices-actions">
                    <span class="hint">{{ selected.items.length }} Geräte</span>
                    <button type="button" class="btn-primary scan-all" :disabled="!canIssue" @click="scanGlobal">Scannen</button>
                </div>
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
                    <span v-if="getCode(item)" class="hint">{{ getCode(item) }}</span>
                    <span class="label">Status</span>
                    <span class="hint">{{ statusLabel(item.status) }}</span>
                    <button type="button" class="btn-secondary scan-button" :disabled="!canIssue || item.status !== 'requested'" @click="scanItem(item)">
                        Scan
                    </button>
                </article>
            </div>
        </section>

        <p v-else class="meta">Reservierung wird geladen oder wurde nicht gefunden.</p>
    </div>

    <ScanModal
        v-if="showScanner"
        :title="scanItemTarget ? 'Gerät für diese Position scannen' : 'Gerät scannen'"
        @scanned="handleScanned"
        @close="closeScan"
    />

    <div v-if="pendingCode" class="overlay">
        <div class="card dialog">
            <h3>Gerät nicht in der Reservierung</h3>
            <p>
                {{ pendingEquipment?.name || pendingCode }} gehört nicht zu dieser Reservierung.
                Soll es hinzugefügt werden?
            </p>

            <label>
                Anstelle eines anderen Geräts?
                <select v-model="replaceItemId">
                    <option value="">Nein, zusätzlich hinzufügen</option>
                    <option v-for="item in replaceable" :key="item.id" :value="item.id">
                        {{ getName(item) }}
                    </option>
                </select>
            </label>

            <div class="dialog-actions">
                <button type="button" class="btn-secondary" @click="cancelAdd">Abbrechen</button>
                <button type="button" class="btn-primary" @click="confirmAdd">Hinzufügen</button>
            </div>
        </div>
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
    gap: var(--space-s);
    align-items: start;
    margin-top: var(--space-l);
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

.devices-actions {
    display: flex;
    align-items: center;
    gap: var(--space-s);
}

.scan-all {
    min-height: 32px;
    padding: 0 var(--space-m);
    font-size: var(--text-label-size);
}

.scan-button {
    margin-top: var(--space-s);
    min-height: 36px;
    font-size: var(--text-label-size);
}

.overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-l);
    background: rgba(17, 24, 39, 0.5);
}

.dialog {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    width: 100%;
    max-width: 420px;
    padding: var(--space-l);
}

.dialog h3,
.dialog p {
    margin: 0;
}

.dialog-actions {
    display: flex;
    gap: var(--space-s);
}

.dialog-actions button {
    flex: 1;
}

.error-text {
    padding: var(--space-s) var(--space-m);
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-radius: var(--radius-s);
}
</style>
