<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useCartStore } from '@/stores/CartStore.ts';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import { Trash2 } from 'lucide-vue-next';

const cartStore = useCartStore();
const equipmentStore = useEquipmentStore();

const items = ref<{
    id: string;
    name: string;
    model: string;
    type: string;
}[]>([]);

const startDate = ref('');
const endDate = ref('');

type Period = Record<string, string>;

const unavailable = ref<Record<string, Period[]>>({});

function formatDateTime(value: string): string {
    if (!value) return '';

    return new Date(value).toLocaleString('de-AT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function toLocalInput(date: Date): string {
    const pad = (n: number) => {
        return String(n).padStart(2, '0');
    };

    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
        + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
}

const minStart = toLocalInput(new Date());

function formatPeriod(period: Period): string {
    const from = period.from ?? period.start ?? period.startDatetime;
    const to = period.to ?? period.end ?? period.endDatetime;

    return formatDateTime(from) + ' – ' + formatDateTime(to);
}

const availabilityChecked = ref(false);
const available = ref(false);
const loading = ref(false);
const message = ref('');
const messageKind = ref<'success' | 'error' | 'info'>('info');

let requestId = 0;

const canCheckAvailability = computed(() => {
    return cartStore.items.length > 0 &&
        startDate.value !== '' &&
        endDate.value !== '' &&
        new Date(startDate.value).getTime() >= Date.now() - 60000 &&
        new Date(startDate.value).getTime() < new Date(endDate.value).getTime();
});

const equipmentCount = computed(() => {
    return items.value.length;
});

async function loadItems() {
    const results = await Promise.all(
        cartStore.items.map(id => {
            return equipmentStore.fetchEquipmentById(id);
        })
    );

    items.value = results.filter(item => {
        return item !== null;
    });
}

onMounted(loadItems);

watch(
    () => {
        return [...cartStore.items];
    },
    loadItems
);

function resetAvailability() {
    requestId++;
    loading.value = false;
    availabilityChecked.value = false;
    available.value = false;
    unavailable.value = {};
    message.value = '';
    messageKind.value = 'info';
}

watch([startDate, endDate], () => {
    resetAvailability();

    if (startDate.value && new Date(startDate.value).getTime() < Date.now() - 60000) {
        message.value = 'Der Start darf nicht in der Vergangenheit liegen.';
        messageKind.value = 'error';
        return;
    }

    if (startDate.value && endDate.value && new Date(endDate.value).getTime() <= new Date(startDate.value).getTime()) {
        message.value = 'Das Ende muss nach dem Start liegen.';
        messageKind.value = 'error';
        return;
    }

    console.log(startDate.value, endDate.value);

    checkAvailability();
});

onMounted(() => {
    const now = new Date();
    startDate.value = toLocalInput(now);
    const end = new Date(now);
    end.setHours(17, 45, 0, 0);

    if (now >= end) {
        end.setDate(end.getDate() + 1);
    }
    endDate.value = toLocalInput(end);
    loadItems();
});

function removeEquipment(id: string) {
    cartStore.remove(id);
    resetAvailability();
    checkAvailability();
}

async function checkAvailability() {
    if (!canCheckAvailability.value) return;

    resetAvailability();
    const myRequest = requestId;
    loading.value = true;

    try {
        const from = new Date(startDate.value).toISOString();
        const to = new Date(endDate.value).toISOString();

        const res = await fetch('/api/v1/equipment/calendar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                equipmentIds: cartStore.items,
                from,
                to
            })
        });

        if (!res.ok) {
            throw new Error('Kalender konnte nicht geladen werden.');
        }

        const calendar = await res.json();
        console.log('Kalender-Antwort:', calendar);

        if (myRequest !== requestId) return;

        if (Array.isArray(calendar)) {
            for (const equipment of calendar) {
                unavailable.value[equipment.id] = equipment.notAvailable;
            }
        }

        available.value = Array.isArray(calendar) &&
            calendar.length === cartStore.items.length &&
            calendar.every((equipment: {
                id: string;
                notAvailable: unknown[];
            }) => {
                return equipment.notAvailable.length === 0;
            });

        availabilityChecked.value = true;

        message.value = available.value
            ? 'Alle ausgewählten Geräte sind im gewünschten Zeitraum verfügbar.'
            : 'Die ausgewählten Geräte sind im gewünschten Zeitraum nicht vollständig verfügbar.';

        messageKind.value = available.value ? 'success' : 'error';

    } catch (error) {
        if (myRequest !== requestId) return;

        message.value = 'Fehler bei der Verfügbarkeitsprüfung.';
        messageKind.value = 'error';
        console.error(error);
    } finally {
        if (myRequest === requestId) {
            loading.value = false;
        }
    }
}

async function submitReservation() {
    loading.value = true;
    message.value = '';

    try {
        const startDatetime = new Date(startDate.value).toISOString();
        const endDatetime = new Date(endDate.value).toISOString();

        const requestBody = {
            startDatetime,
            endDatetime,
            equipmentIds: cartStore.items
        };
        console.log('Reservierungsdaten:', requestBody);

        const res = await fetch('/api/v1/reservations', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        const responseText = await res.text();

        console.log('Reservierungs-Status:', res.status);
        console.log('Reservierungs-Antwort:', responseText);

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${responseText}`);
        }

        message.value = 'Reservierung erfolgreich erstellt!';
        messageKind.value = 'success';

    } catch (error) {
        console.error('Fehler beim Absenden der Reservierung:', error);

        message.value = 'Fehler beim Absenden der Reservierungsanfrage.';
        messageKind.value = 'error';

    } finally {
        loading.value = false;
    }
}
</script>


<template>
    <h1>Meine Reservierung</h1>

    <section class="reservation-panel">

        <h3>Reservierungszeit</h3>

        <div class="date-inputs">
            <label>
                Von
                <input type="datetime-local" v-model="startDate" :min="minStart">
            </label>
            <label>
                Bis
                <input type="datetime-local" v-model="endDate" :min="startDate || minStart">
            </label>
        </div>

        <p v-if="loading" class="meta">Verfügbarkeit wird geprüft...</p>

    </section>

    <section class="reservation-equipment-panel">

        <div class="availability-heading">
            <div>
                <h2>Verfügbarkeit</h2>
                <p class="meta" v-if="startDate && endDate">
                    {{ formatDateTime(startDate) }} – {{ formatDateTime(endDate) }}
                </p>
                <p class="meta" v-else>
                    Bitte zuerst einen Zeitraum auswählen.
                </p>
            </div>
            <span class="equipment-count">
                {{ equipmentCount }} Geräte
            </span>
        </div>

        <div v-if="items.length > 0" class="equipment-list">

            <article v-for="item in items" :key="item.id" class="equipment-item">
                <div class="equipment-header">
                    <div class="equipment-name">
                        <div class="equipment-image">
                            <span class="image-placeholder"></span>
                        </div>
                        <span>{{ item.name }}</span>
                    </div>
                    <button type="button" class="remove-button" aria-label="Equipment entfernen" @click="removeEquipment(item.id)">
                        <Trash2 />
                    </button>
                </div>

                <ul v-if="unavailable[item.id]?.length" class="unavailable">
                    <li v-for="(period, i) in unavailable[item.id]" :key="i">
                        Nicht verfügbar: {{ formatPeriod(period) }}
                    </li>
                </ul>
            </article>

        </div>

        <div v-else class="empty-state">
            <span class="empty-icon">▣</span>
            <p>Noch kein Equipment ausgewählt.</p>
            <span class="hint">
                Wähle zuerst Geräte aus dem Inventar aus.
            </span>
        </div>

        <div v-if="message" class="status-message" :class="'message-' + messageKind" role="status">
            {{ message }}
        </div>

        <div v-if="availabilityChecked && available" class="availability-success">
            <span class="success-icon">✓</span>
            <span>Alle {{ equipmentCount }} Geräte verfügbar</span>
        </div>

        <div class="reservation-actions">
            <button type="button" class="btn-secondary back-button" @click="$router.back()">
                Zurück
            </button>
            <button type="button" class="btn-primary submit-button" :disabled="!availabilityChecked || !available || loading" @click="submitReservation">
                {{ loading ? 'Wird gesendet...' : 'Reservieren' }}
            </button>
        </div>

    </section>
</template>


<style scoped>
.reservation-page h1 {
    margin: 0 0 var(--space-xs);
    font-size: 21px;
    line-height: 28px;
}

.reservation-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    padding: var(--space-l);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-section);
    box-shadow: var(--shadow-card);
}

.reservation-panel h3 {
    margin: 0;
    font-size: 15px;
}

.date-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-s);
}

.date-inputs label {
    min-width: 0;
    gap: var(--space-xs);
}

.date-inputs input {
    width: 100%;
    min-width: 0;
    min-height: 36px;
    height: 36px;
    padding: 0 var(--space-s);
    font-size: 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-s);
}

.availability-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}

.availability-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-s);
}

.availability-heading h2 {
    margin: 0;
    font-size: 18px;
}

.availability-heading .meta {
    margin: 2px 0 0;
    font-size: 11px;
}

.equipment-count {
    flex-shrink: 0;
    font-size: 11px;
    color: var(--color-text-secondary);
}

.equipment-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}

.equipment-item {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
    padding: var(--space-s) var(--space-m);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-tab);
}

.remove-button svg {
    width: 16px;
    height: 16px;
}

.equipment-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 29px;
    gap: var(--space-s);
}

.equipment-name {
    display: flex;
    align-items: center;
    gap: var(--space-s);
    min-width: 0;
    font-size: 12px;
    line-height: 18px;
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
}

.equipment-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    background: var(--color-soft-surface);
    border-radius: 6px;
    font-size: 12px;
}

.remove-button {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    padding: 0;
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border: none;
    border-radius: var(--radius-s);
    cursor: pointer;
    font-size: 14px;
}

.remove-button:hover {
    background: #fbdada;
}

.unavailable {
    margin: 0;
    padding: var(--space-xs) var(--space-s);
    list-style: none;
    font-size: 11px;
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-radius: var(--radius-s);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-s);
    padding: var(--space-xl);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-card);
    text-align: center;
}

.empty-state p {
    margin: 0;
    font-weight: var(--font-bold);
}

.empty-icon {
    font-size: 28px;
    color: var(--color-muted);
}

.availability-success {
    display: flex;
    align-items: center;
    gap: var(--space-s);
    padding: var(--space-s) var(--space-m);
    color: var(--color-available-text);
    background: var(--color-available-bg);
    border: 1px solid var(--color-available-accent);
    border-radius: var(--radius-tab);
    font-size: 12px;
    font-weight: var(--font-bold);
}

.success-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: var(--color-available-text);
    color: white;
    border-radius: 50%;
}

.status-message {
    padding: var(--space-s) var(--space-m);
    border-radius: var(--radius-tab);
    font-size: 12px;
    line-height: 18px;
}

.message-success {
    color: var(--color-available-text);
    background: var(--color-available-bg);
}

.message-error {
    color: var(--color-error-text);
    background: var(--color-error-bg);
}

.message-info {
    color: var(--color-text-primary);
    background: var(--color-info);
}

.reservation-actions {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: var(--space-s);
    margin-top: var(--space-xs);
}

.back-button {
    min-height: 42px;
    padding: 0 var(--space-l);
    font-size: 12px;
}

.submit-button {
    min-height: 42px;
    font-size: 13px;
    border-radius: var(--radius-tab);
}

@media (max-width: 380px) {
    .reservation-page {
        padding: var(--space-s);
        gap: var(--space-s);
    }

    .reservation-panel {
        padding: var(--space-m);
    }

    .equipment-name {
        font-size: 11px;
    }

    .date-inputs {
        gap: 6px;
    }
}

.reservation-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    width: 100%;
    max-width: 560px;
    margin: 0 auto;
    padding: var(--space-m);
}

.reservation-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    padding: var(--space-l);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-section);
    box-shadow: var(--shadow-card);
}

.reservation-equipment-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    padding: var(--space-l);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-section);
    box-shadow: var(--shadow-card);
}


.availability-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-s);
}

.availability-heading h2 {
    margin: 0;
    font-size: 18px;
}

.availability-heading .meta {
    margin: 2px 0 0;
    font-size: 11px;
}


.equipment-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
    margin: 0;
}


.reservation-actions {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: var(--space-s);
    padding-top: var(--space-m);
    border-top: 1px solid var(--color-border);
}

.back-button,
.submit-button {
    min-height: 42px;
}


@media (max-width: 380px) {
    .reservation-page {
        padding: var(--space-s);
        gap: var(--space-s);
    }

    .reservation-panel,
    .reservation-equipment-panel {
        padding: var(--space-m);
    }
}

</style>
