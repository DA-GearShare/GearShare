<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useReservationStore } from "@/stores/ReservationStore.ts";

const props = defineProps<{
    id: string;
    title: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const reason = ref<HTMLInputElement | null>(null);
const reservationStore = useReservationStore();

onMounted(() => {
    reason.value?.focus();
});

function sendReject() {
    reservationStore.rejectReservation(props.id, reason.value);
}
</script>

<template>
    <div class="overlay" @click.self="emit('close')">
        <div class="card modal">
            <h3>{{ title }}</h3>
            <p class="meta">Wollen Sie die Reservierung ablehnen?</p>

            <form class="manual" @submit.prevent="sendReject">
                <input type="text" name="message" v-model="reason" placeholder="Ablehnungsgrund angeben.." />
                <button type="submit" class="btn-primary">Ablehnen</button>
            </form>

            <button type="button" class="btn-secondary" @click="emit('close')">Abbrechen</button>
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

.modal {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    width: 100%;
    max-width: 420px;
    padding: var(--space-l);
}

.modal h3,
.modal p {
    margin: 0;
}

.manual {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}
</style>
