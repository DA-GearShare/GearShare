<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useReservationStore } from '@/stores/ReservationStore';
import ReservationAdminCard from '@/components/ReservationAdminCard.vue';
import ReservationDetailCard from '@/components/ReservationDetailCard.vue';
import type { Reservation, ReservationItem } from '@/types/reservation';

const reservationStore = useReservationStore();
const selectedId = ref('');

onMounted(() => {
    reservationStore.fetchReservations();
});

const selected = computed(() => {
    const all: Reservation[] = reservationStore.reservations || [];
    return all.find((r) => {
        return r.id === selectedId.value;
    });
});

function selectReservation(id: string) {
    selectedId.value = id;
}

// TODO: globaler Scan
function scanGlobal() {
}

// TODO: Scan für ein bestimmtes Gerät
function scanItem(item: ReservationItem) {
}
</script>

<template>
    <main class="admin-reservations">
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

        <!-- TODO: Rückfrage-Dialog (Gerät hinzufügen / ersetzen, Dropdown) -->
    </main>
</template>

<style scoped>
.admin-reservations {
    max-width: 900px;
    margin: var(--space-xl) auto;
    padding: 0 var(--space-l);
}
</style>
