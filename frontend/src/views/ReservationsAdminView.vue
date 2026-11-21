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
    if (selectedId.value === id) {
        selectedId.value = '';
    } else {
        selectedId.value = id;
    }
}

function scanGlobal() {
}

function scanItem(item: ReservationItem) {
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

</template>

<style scoped>

</style>
