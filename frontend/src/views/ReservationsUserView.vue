<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useReservationStore } from '@/stores/ReservationStore';
import ReservationUserCard from '@/components/ReservationUserCard.vue';
import type { Reservation } from '@/types/reservation';

const reservationStore = useReservationStore();

const activeTab = ref('current');
const currentStatus = ['requested', 'active'];

onMounted(() => {
    reservationStore.fetchReservations();
});

function isCurrent(reservation: Reservation) {
    return currentStatus.includes(reservation.status);
}

const visibleReservations = computed(() => {
    const all: Reservation[] = reservationStore.reservations || [];

    if (activeTab.value === 'current') {
        return all.filter(isCurrent);
    }
    return all.filter((reservation) => {
        return !isCurrent(reservation);
    });
});

const title = computed(() => {
    if (activeTab.value === 'current') {
        return 'Aktuelle Reservierungen';
    }
    return 'Historie';
});
</script>

<template>
    <h1>Meine Reservierungen</h1>

    <div class="tabs">
        <button type="button" class="tab" :class="{ active: activeTab === 'current' }" @click="activeTab = 'current'">
            Aktuell
        </button>
        <button type="button" class="tab" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">
            Historie
        </button>
    </div>

    <div class="list-header">
        <h2>{{ title }}</h2>
        <span v-if="activeTab === 'history'" class="meta">
            {{ visibleReservations.length }} Reservierungen
        </span>
    </div>

    <p v-if="reservationStore.isLoading" class="meta">Reservierungen werden geladen...</p>

    <p v-else-if="reservationStore.error" class="message-error">
        {{ reservationStore.error }}
    </p>

    <p v-else-if="visibleReservations.length === 0" class="message-info">
        Keine Reservierungen vorhanden.
    </p>

    <div v-else class="card-list">
        <ReservationUserCard
            v-for="reservation in visibleReservations"
            :key="reservation.id"
            :reservation="reservation"
        />
    </div>
</template>

<style scoped>
.tabs {
    display: flex;
    gap: var(--space-xs);
    padding: var(--space-xs);
    margin-bottom: var(--space-xl);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.tab {
    flex: 1;
    min-height: 40px;
    font: inherit;
    font-size: var(--text-body-size);
    color: var(--color-text-primary);
    background: transparent;
    border: none;
    border-radius: var(--radius-tab);
    cursor: pointer;
}

.tab.active {
    font-weight: var(--font-bold);
    color: var(--color-surface);
    background: var(--color-navy);
}

.list-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
}
</style>
