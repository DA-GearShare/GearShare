<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useReservationStore } from '@/stores/ReservationStore';
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";
import ReservationAdminCard from '@/components/ReservationAdminCard.vue';
import ReservationDetailCard from '@/components/ReservationDetailCard.vue';
import ScanModal from "@/components/ScanModal.vue";

export type ReservationStatus =
    'requested' | 'denied' | 'cancelled' | 'active' | 'returned';

export interface ReservationItem {
    id: string;
    reservationId: string;
    requestedEquipmentId: string | null;
    requestedPoolId: string | null;
    status: string;
    issuedAt: string | null;
    returnedAt: string | null;
}

export interface Reservation {
    id: string;
    requestedByUserId: string;
    responsibleUserId: string | null;
    status: ReservationStatus;
    startDatetime: string;
    endDatetime: string;
    isOverdue: boolean;
    createdAt: string;
    items: ReservationItem[];
}

const reservationStore = useReservationStore();
const equipmentStore = useEquipmentStore();
const selectedId = ref('');
const equipmentNames = ref<Record<string, string>>({});
const showScanner = ref(false);
const scanItemTarget = ref<ReservationItem | null>(null);
const pendingCode = ref('');
const pendingEquipment = ref<{ name?: string } | null>(null);
const replaceItemId = ref('');

onMounted(() => {
    reservationStore.fetchReservations();
});

const selectedReservation = computed(() => {
    const all: Reservation[] = reservationStore.reservations || [];
    return all.find((r) => {
        return r.id === selectedId.value;
    });
});

const replaceable = computed(() => {
    const items = selectedReservation.value?.items || [];
    return items.filter((item) => {
        return item.status === 'requested';
    });
});

async function loadNames(reservation: Reservation) {
    for (const item of reservation.items) {
        const id = item.requestedEquipmentId;
        if (!id || equipmentNames.value[id]) {
            continue;
        }
        const equipment = await equipmentStore.fetchEquipmentById(id);
        if (equipment) {
            equipmentNames.value[id] = equipment.name;
        }
    }
}

function getName(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return 'Unbekanntes Gerät';
    }
    return equipmentNames.value[item.requestedEquipmentId] || 'Wird geladen…';
}

function selectReservation(id: string) {
    if (selectedId.value === id) {
        selectedId.value = '';
        return;
    }

    selectedId.value = id;
    if (selectedReservation.value) {
        loadNames(selectedReservation.value);
    }
}

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
    if (!selectedReservation.value) {
        return;
    }

    const updated = await reservationStore.fetchIssueItem(selectedReservation.value.id, item.id, { code });
    if (updated) {
        replaceReservation(updated);
    }
}

async function issueGlobal(code: string) {
    if (!selectedReservation.value) {
        return;
    }

    const result = await reservationStore.fetchIssueGlobal(selectedReservation.value.id, {
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
    if (!selectedReservation.value) {
        return;
    }

    const payload: Record<string, unknown> = {
        code: pendingCode.value,
        confirmAdd: true,
    };

    if (replaceItemId.value) {
        payload.replaceItemId = replaceItemId.value;
    }

    const updated = await reservationStore.fetchIssueGlobal(selectedReservation.value.id, payload);
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
    <p class="eyebrow">Verwaltung</p>
    <h1>Admin-Reservierungen</h1>

    <p v-if="reservationStore.error" class="message-error">{{ reservationStore.error }}</p>

    <div class="layout">
        <section class="card-list">
            <template v-for="reservation in reservationStore.reservations" :key="reservation.id">
                <ReservationAdminCard
                    :reservation="reservation"
                    :selected="reservation.id === selectedId"
                    @open="selectReservation"
                />

                <ReservationDetailCard
                    v-if="reservation.id === selectedId"
                    :reservation="reservation"
                    @scan-global="scanGlobal"
                    @scan-item="scanItem"
                />
            </template>
        </section>
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
</style>
