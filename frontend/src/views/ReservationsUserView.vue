<script setup lang="ts">
import { onMounted } from 'vue';
import { useReservationStore } from '@/stores/ReservationStore';
import ReservationCard from '@/components/ReservationCard.vue';

const reservationStore = useReservationStore();

onMounted(() => {
    reservationStore.fetchReservations();
});

function openReservation(id: string) {
    router.push(`/reservations/${id}`);
}
</script>

<template>
    <main class="user-reservations">
        <h1>Meine Reservierungen</h1>

        <p v-if="reservationStore.isLoading">Reservierungen werden geladen...</p>

        <p v-else-if="reservationStore.error" class="error">
            {{ reservationStore.error }}
        </p>

        <p v-else-if="!reservationStore.reservations || reservationStore.reservations.length === 0">
            Es gibt aktuell keine Reservierungen.
        </p>

        <div v-else class="reservation-list">
            <ReservationCard
                v-for="reservation in reservationStore.reservations"
                :key="reservation.id"
                :reservation="reservation"
                @open="openReservation"
            />
        </div>
    </main>
</template>

<style scoped>
.user-reservations {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 24px;
}

h1 {
    margin-bottom: 28px;
}

.reservation-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.error {
    color: #dc2626;
}
</style>
