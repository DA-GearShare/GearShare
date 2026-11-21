<script setup lang="ts">
export interface Reservation {
    id: string;
    requestedByUserId: string;
    responsibleUserId: string | null;
    status: 'requested' | 'accepted' | 'denied' | 'cancelled' | 'active' | 'returned';
    startDatetime: string;
    endDatetime: string;
    isOverdue: boolean;
    createdAt: string;
}

defineProps<{
    reservation: Reservation;
}>();

const emit = defineEmits<{
    open: [id: string];
}>();
</script>

<template>
    <article class="reservation-card">
        <div class="reservation-info">
            <h2>Reservierung #{{ reservation.id }}</h2>

            <p>Benutzer: {{ reservation.requestedByUserId }}</p>
            <p>Status: {{ reservation.status }}</p>
        </div>

        <button type="button" @click="emit('open', reservation.id)">
            Reservierung öffnen
        </button>
    </article>
</template>

<style scoped>
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

@media (max-width: 600px) {
    .reservation-card {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
