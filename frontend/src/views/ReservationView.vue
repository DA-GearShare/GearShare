<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useCartStore } from '@/stores/CartStore.ts';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import EquipmentCard from "@/components/EquipmentCard.vue";

const cart = useCartStore();
const store = useEquipmentStore();

const items = ref<{ id: string; name: string; model: string; type: string }[]>([]);

const startDate = ref('');
const endDate = ref('');

const availabilityChecked = ref(false);
const available = ref(false);
const loading = ref(false);
const message = ref('');

async function loadItems() {
    const results = await Promise.all(cart.items.map((id) => {
        return store.fetchEquipmentById(id);
    }));
    items.value = results.filter((item) => {
        return item !== null;
    });
}

onMounted(loadItems);
watch(() => {
    return cart.items.length;
}, loadItems);

const canCheckAvailability = computed(() => {
    return cart.items.length > 0 &&
        startDate.value !== '' &&
        endDate.value !== '' &&
        startDate.value <= endDate.value;
});

function resetAvailability() {
    availabilityChecked.value = false;
    available.value = false;
    message.value = '';
}

function removeEquipment(id: string) {
    cart.remove(id);
    resetAvailability();
}

async function checkAvailability() {
    if (!canCheckAvailability.value) return;

    loading.value = true;
    resetAvailability();

    try {
        const from = new Date(`${startDate.value}T00:00:00`).toISOString();
        const to = new Date(`${endDate.value}T23:59:59`).toISOString();

        const response = await fetch('/api/v1/equipment/calendar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                equipmentIds: cart.items,
                from,
                to
            })
        });

        if (!response.ok) {
            throw new Error('Kalender konnte nicht geladen werden.');
        }

        const calendar = await response.json();

        const requestedStart = new Date(from).getTime();
        const requestedEnd = new Date(to).getTime();

        available.value = calendar.some((slot: {
            start: string
            end: string
            status: string
        }) => {
            return slot.status === 'available' &&
                new Date(slot.start).getTime() <= requestedStart &&
                new Date(slot.end).getTime() >= requestedEnd;
        });

        availabilityChecked.value = true;

        message.value = available.value
            ? 'Alle ausgewählten Geräte sind im gewünschten Zeitraum verfügbar.'
            : 'Die ausgewählten Geräte sind im gewünschten Zeitraum nicht vollständig verfügbar.';

    } catch (error) {
        message.value = 'Fehler bei der Verfügbarkeitsprüfung.';
        console.error(error);
    } finally {
        loading.value = false;
    }
}

async function submitReservation() {
    if (!availabilityChecked.value || !available.value) return;

    loading.value = true;
    message.value = '';

    try {
        const startDatetime = new Date(`${startDate.value}T00:00:00`).toISOString();
        const endDatetime = new Date(`${endDate.value}T23:59:59`).toISOString();

        const response = await fetch('/api/v1/reservations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                startDatetime,
                endDatetime,
                equipmentIds: cart.items
            })
        });

        if (!response.ok) {
            throw new Error('Reservierungsanfrage konnte nicht erstellt werden.');
        }

        const reservation = await response.json();

        message.value = `Reservierungsanfrage erfolgreich erstellt! ID: ${reservation.id}`;

        cart.clear();
        startDate.value = '';
        endDate.value = '';
        availabilityChecked.value = false;
        available.value = false;

    } catch (error) {
        message.value = 'Fehler beim Absenden der Reservierungsanfrage.';
        console.error(error);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="reservation-page">
        <h1>Meine Reservierung</h1>

        <h2>Ausgewähltes Equipment</h2>

        <p v-if="cart.items.length === 0">
            Noch kein Equipment ausgewählt.
        </p>

        <ul v-else class="card-list cart-list">
            <li v-for="item in items" :key="item.id" class="cart-item">
                <EquipmentCard :id="item.id" :name="item.name" :model="item.model" :type="item.type" />

                <button type="button" class="btn-secondary" @click="removeEquipment(item.id)">
                    Entfernen
                </button>
            </li>
        </ul>

        <h2>Zeitraum auswählen</h2>

        <div class="date-inputs">
            <label>
                Von:
                <input type="date" v-model="startDate" @change="resetAvailability">
            </label>

            <label>
                Bis:
                <input type="date" v-model="endDate" :min="startDate || undefined" @change="resetAvailability">
            </label>
        </div>

        <button type="button" class="btn-secondary" :disabled="!canCheckAvailability || loading" @click="checkAvailability">
            {{ loading ? 'Wird geprüft...' : 'Verfügbarkeit prüfen' }}
        </button>

        <p v-if="message" class="status-message">
            {{ message }}
        </p>

        <button type="button" class="btn-primary" :disabled="!availabilityChecked || !available || loading" @click="submitReservation">
            {{ loading ? 'Wird gesendet...' : 'Reservierung absenden' }}
        </button>
    </div>
</template>

<style scoped>
.reservation-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
}

.cart-list {
    padding: 0;
    list-style: none;
}

.cart-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-s);
}

.date-inputs {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-m);
}

.date-inputs label {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.status-message {
    padding: var(--space-m);
    border-radius: var(--radius-s);
}
</style>
