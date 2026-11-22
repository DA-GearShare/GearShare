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
    router.push(`/admin/reservierungen/${id}`);
}
</script>

<template>
    <p class="eyebrow">Verwaltung</p>
    <div class="page-header">
        <h1>Admin</h1>
        <span class="meta">{{ reservationStore.reservations?.length ?? 0 }} Reservierungen</span>
    </div>
    <h2>Reservierungen</h2>

    <p v-if="reservationStore.error" class="message-error">{{ reservationStore.error }}</p>

    <section class="card-list">
        <ReservationAdminCard
            v-for="reservation in reservationStore.reservations"
            :key="reservation.id"
            :reservation="reservation"
            @open="openReservation"
            selected
        />
    </section>
</template>

<style scoped>
.page-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
}
</style>
