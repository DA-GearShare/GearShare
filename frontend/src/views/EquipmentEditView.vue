<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { Check } from 'lucide-vue-next';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';
import EquipmentCode from '@/components/EquipmentCode.vue';

const route = useRoute();
const store = useEquipmentStore();
const id = route.params.id as string;

const loading = ref(true);
const notFound = ref(false);
const error = ref<string | null>(null);
const saved = ref<{ id: string; code: string } | null>(null);
const showConfirm = ref(false);
const saving = ref(false);

const name = ref('');
const location = ref('');
const type = ref('');
const model = ref('');
const code = ref('');

onMounted(async () => {
    const item = await store.fetchEquipmentById(id);
    if (item) {
        name.value = item.name ?? '';
        location.value = item.location ?? '';
        type.value = item.type ?? '';
        model.value = item.model ?? '';
        code.value = item.code ?? '';
    } else {
        notFound.value = true;
    }
    loading.value = false;
});

function openConfirm() {
    error.value = null;
    showConfirm.value = true;
}

function closeConfirm() {
    showConfirm.value = false;
}

function keepEditing() {
    saved.value = null;
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

    const result = await store.updateEquipment(id, payload);
    saving.value = false;
    showConfirm.value = false;

    if (result) {
        saved.value = { id, code: result.code ?? code.value };
    } else {
        error.value = 'Das Equipment konnte nicht gespeichert werden.';
    }
}
</script>

<template>
    <h1 v-if="!saved">Equipment bearbeiten</h1>

    <p v-if="loading" class="meta">Lädt ...</p>

    <div v-else-if="notFound" class="section-card result-card">
        <p>Dieses Gerät wurde nicht gefunden.</p>
        <RouterLink to="/inventar" class="btn-primary">Zur Übersicht</RouterLink>
    </div>

    <div v-else-if="saved" class="section-card success-card">
        <div class="success-icon">
            <Check />
        </div>

        <h2>Änderungen gespeichert</h2>
        <p class="success-name">{{ name }}</p>
        <p class="meta">Code: <strong>{{ saved.code }}</strong></p>

        <EquipmentCode :code="saved.code" :name="name" />

        <div class="success-actions">
            <RouterLink to="/inventar" class="btn-primary">Zur Übersicht</RouterLink>
            <button type="button" class="btn-secondary" @click="keepEditing">Weiter bearbeiten</button>
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
            Code
            <input v-model="code" type="text" maxlength="255" placeholder="Barcode scannen oder eingeben">
            <span class="hint">Leer lassen, dann bleibt der automatisch erzeugte Code bestehen.</span>
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
            <h2 id="confirm-title">Änderungen speichern?</h2>
            <p>Bitte prüfe die Angaben, bevor du sie übernimmst.</p>

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
                    <dd>{{ code.trim() || 'Bleibt unverändert' }}</dd>
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
</style>
