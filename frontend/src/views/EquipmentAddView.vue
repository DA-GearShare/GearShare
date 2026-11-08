<script setup lang="ts">
import { ref } from 'vue';
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";
import EquipmentCode from '@/components/EquipmentCode.vue';
import { Check } from 'lucide-vue-next';

const equipmentStore = useEquipmentStore();
const error = ref<string | null>(null);
const created = ref<{ id: string; code: string } | null>(null);
const showConfirm = ref(false);
const saving = ref(false);

const name = ref('');
const location = ref('');
const type = ref('');
const model = ref('');
const code = ref('');

function openConfirm() {
    error.value = null;
    showConfirm.value = true;
}

function closeConfirm() {
    showConfirm.value = false;
}

function addAnother() {
    created.value = null;
    name.value = '';
    location.value = '';
    type.value = '';
    model.value = '';
    code.value = '';
}

async function save() {
    saving.value = true;

    const payload: Record<string, string> = {
        name: name.value.trim(),
        location: location.value.trim(),
        type: type.value.trim(),
        model: model.value.trim(),
    };

    if (code.value.trim() !== '') {
        payload.code = code.value.trim();
    }

    const result = await equipmentStore.createEquipment(payload);
    saving.value = false;
    showConfirm.value = false;

    if (result) {
        created.value = result;
    } else {
        error.value = 'Das Equipment konnte nicht gespeichert werden.';
    }
}
</script>

<template>
    <h1 v-if="!created">Equipment hinzufügen</h1>

    <div v-if="created" class="section-card success-card">
        <div class="success-icon">
            <Check />
        </div>

        <h2>Gerät angelegt</h2>
        <p class="success-name">{{ name }}</p>
        <p class="meta">Code: <strong>{{ created.code }}</strong></p>

        <EquipmentCode :code="created.code" :name="name" />

        <div class="success-actions">
            <RouterLink to="/inventar" class="btn-primary">Zur Übersicht</RouterLink>
            <button type="button" class="btn-secondary" @click="addAnother">Weiteres Gerät anlegen</button>
        </div>
    </div>

    <form v-else class="equipment-form" @submit.prevent="openConfirm">
        <label>
            Bezeichnung
            <input v-model="name" type="text" required maxlength="255">
        </label>

        <label>
            Ort
            <input v-model="location" type="text" required maxlength="255">
        </label>

        <label>
            Typ
            <input v-model="type" type="text" required maxlength="255">
        </label>

        <label>
            Modell
            <input v-model="model" type="text" required maxlength="255">
        </label>

        <label>
            Code (optional)
            <input v-model="code" type="text" maxlength="255" placeholder="Barcode scannen oder eingeben">
            <span class="hint">Leer lassen, dann wird automatisch ein Code erzeugt.</span>
        </label>

        <p v-if="error" class="message-error">{{ error }}</p>

        <div class="toolbar">
            <button type="submit" class="btn-primary">Speichern</button>
            <RouterLink to="/inventar" class="btn-secondary">Abbrechen</RouterLink>
        </div>
    </form>

    <div
        v-if="showConfirm"
        class="modal-backdrop"
        @click.self="closeConfirm"
        @keydown.esc="closeConfirm"
    >
        <div class="section-card modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <h2 id="confirm-title">Equipment speichern?</h2>
            <p>Bitte prüfen Sie die Angaben, bevor Sie das Gerät anlegen.</p>

            <dl class="summary">
                <div>
                    <dt>Bezeichnung</dt>
                    <dd>{{ name }}</dd>
                </div>
                <div>
                    <dt>Ort</dt>
                    <dd>{{ location }}</dd>
                </div>
                <div>
                    <dt>Typ</dt>
                    <dd>{{ type }}</dd>
                </div>
                <div>
                    <dt>Modell</dt>
                    <dd>{{ model }}</dd>
                </div>
                <div>
                    <dt>Code</dt>
                    <dd>{{ code.trim() || 'Wird automatisch erzeugt' }}</dd>
                </div>
            </dl>

            <div class="toolbar">
                <button type="button" class="btn-primary" :disabled="saving" @click="save">
                    {{ saving ? 'Speichert ...' : 'Ja, speichern' }}
                </button>
                <button type="button" class="btn-secondary" :disabled="saving" @click="closeConfirm">
                    Zurück
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.equipment-form,
.result-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-l);
    max-width: 480px;
    margin-top: var(--space-xl);
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-l);
    background: rgba(17, 24, 39, 0.5);
}

.modal {
    display: flex;
    flex-direction: column;
    gap: var(--space-l);
    width: 100%;
    max-width: 440px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-card);
}

.modal h2,
.modal p {
    margin: 0;
}

.summary {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    margin: 0;
    padding: var(--space-l);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.summary dt {
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    font-weight: var(--font-bold);
    color: var(--color-text-secondary);
}

.summary dd {
    margin: var(--space-xs) 0 0;
    color: var(--color-text-primary);
    overflow-wrap: anywhere;
}

.success-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-m);
    max-width: 480px;
    margin: var(--space-xl) auto 0;
    text-align: center;
}

.success-card h2,
.success-card p {
    margin: 0;
}

.success-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    color: var(--color-available-text);
    background: var(--color-available-bg);
    border-radius: var(--radius-pill);
}

.success-icon svg {
    width: 28px;
    height: 28px;
}

.success-name {
    font-size: var(--text-card-size);
    line-height: var(--text-card-line);
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
}

.success-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-s);
    margin-top: var(--space-s);
}

.success-card :deep(.qr-code) {
    align-items: center;
}

.success-card :deep(.toolbar) {
    justify-content: center;
}
</style>
