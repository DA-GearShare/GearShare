<script setup lang="ts">
import { ref, computed } from 'vue';
import EquipmentList from '@/views/EquipmentView.vue';

const selectedEquipment = ref<string[]>([]);
const startDate = ref('');
const endDate = ref('');

const availabilityChecked = ref(false);
const available = ref(false);
const loading = ref(false);
const message = ref('');

const canCheckAvailability = computed(() => {
    return selectedEquipment.value.length > 0 &&
        startDate.value !== '' &&
        endDate.value !== '' &&
        startDate.value <= endDate.value;
});

function resetAvailability() {
    availabilityChecked.value = false;
    available.value = false;
    message.value = '';
}

function handleSelect(id: string) {
    if (!selectedEquipment.value.includes(id)) {
        selectedEquipment.value.push(id);
    }

    resetAvailability();
}

function removeEquipment(id: string) {
    selectedEquipment.value =
        selectedEquipment.value.filter(item => {
            return item !== id;
        });

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
                equipmentIds: selectedEquipment.value,
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
        const startDatetime =
            new Date(`${startDate.value}T00:00:00`).toISOString();

        const endDatetime =
            new Date(`${endDate.value}T23:59:59`).toISOString();

        const response = await fetch('/api/v1/reservations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                startDatetime,
                endDatetime,
                items: selectedEquipment.value.map(id => {
                    return {
                        requestedEquipmentId: id
                    };
                })
            })
        });

        if (!response.ok) {
            throw new Error('Reservierungsanfrage konnte nicht erstellt werden.');
        }

        const reservation = await response.json();

        message.value =
            `Reservierungsanfrage erfolgreich erstellt! ID: ${reservation.id}`;

        selectedEquipment.value = [];
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

        <h2>1. Equipment auswählen</h2>

        <EquipmentList picker-mode :exclude-ids="selectedEquipment" @select="handleSelect"/>

        <h2>2. Ausgewähltes Equipment</h2>

        <p v-if="selectedEquipment.length === 0">
            Noch kein Equipment ausgewählt.
        </p>

        <ul v-else>
            <li v-for="id in selectedEquipment" :key="id">
                {{ id }}

                <button type="button" @click="removeEquipment(id)">
                    Entfernen
                </button>
            </li>
        </ul>

        <h2>3. Zeitraum auswählen</h2>

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

        <button type="button" :disabled="!canCheckAvailability || loading" @click="checkAvailability">
            {{ loading ? 'Wird geprüft...' : 'Verfügbarkeit prüfen' }}
        </button>

        <p v-if="message" class="status-message">
            {{ message }}
        </p>

        <button type="button" :disabled="!availabilityChecked || !available || loading" @click="submitReservation">
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
