<script setup lang="ts">
import { onMounted } from 'vue';
import { useReservationStore } from '@/stores/ReservationStore';

const reservationStore = useReservationStore();

onMounted(() => {
    reservationStore.fetchReservations();
});
</script>

<template>
    <main class="admin-reservations">
        <h1>Admin-Reservierungen</h1>

        <p v-if="reservationStore.isLoading">
            Reservierungen werden geladen...
        </p>

        <p v-else-if="reservationStore.error" class="error">
            {{ reservationStore.error }}
        </p>

        <p v-else-if="!reservationStore.reservations || reservationStore.reservations.length === 0">
            Es gibt aktuell keine Reservierungen.
        </p>

        <div v-else class="reservation-list">
            <article
                v-for="reservation in reservationStore.reservations"
                :key="reservation.id"
                class="reservation-card"
            >
                <div class="reservation-info">
                    <h2>Reservierung #{{ reservation.id }}</h2>

                    <p>
                        Benutzer: {{ reservation.userName }}
                    </p>

                    <p>
                        Status: {{ reservation.status }}
                    </p>
                </div>

                <button type="button">
                    Reservierung öffnen
                </button>
            </article>
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

.reservation-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
}

.reservation-info h2 {
    margin: 0 0 12px;
    font-size: 18px;
}

.reservation-info p {
    margin: 6px 0;
}

.error {
    color: #dc2626;
}

@media (max-width: 600px) {
    .reservation-card {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
