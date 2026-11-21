<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useReservationStore } from '@/stores/ReservationStore';
import ReservationAdminCard from '@/components/ReservationAdminCard.vue';

const reservationStore = useReservationStore();
const router = useRouter();

onMounted(() => {
    reservationStore.fetchReservations();
});

function openReservation(id: string) {
    router.push(`/reservierungen/${id}`);
}
</script>

<template>
    <main class="admin-reservations">
        <h1>Admin-Reservierungen</h1>

        <p v-if="reservationStore.isLoading">Reservierungen werden geladen...</p>

        <p v-else-if="reservationStore.error" class="error">
            {{ reservationStore.error }}
        </p>

        <p v-else-if="!reservationStore.reservations || reservationStore.reservations.length === 0">
            Es gibt aktuell keine Reservierungen.
        </p>

        <div v-else class="reservation-list">
            <ReservationAdminCard
                v-for="reservation in reservationStore.reservations"
                :key="reservation.id"
                :reservation="reservation"
                @open="openReservation"
            />
        </div>
    </main>
</template>

<style scoped>
.admin-reservations {
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
