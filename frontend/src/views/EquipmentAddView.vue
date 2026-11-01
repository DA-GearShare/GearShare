<script setup lang="ts">
import { ref } from 'vue';
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";

const equipmentStore = useEquipmentStore();
const error = ref<string | null>(null);
const created = ref<{ id: string; code: string } | null>(null);

const name = ref('');
const location = ref('');
const type = ref('');
const model = ref('');
const code = ref('');

async function submit() {
    error.value = null;

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
    if (result) {
        created.value = result;
    } else {
        error.value = 'Das Equipment konnte nicht gespeichert werden.';
    }
}
</script>

<template>
    <h1>Equipment hinzufügen</h1>

    <div v-if="created" class="section-card result-card">
        <h2>Gerät angelegt</h2>
        <p>Code: <strong>{{ created.code }}</strong></p>
        <div class="toolbar">
            <a :href="`/api/v1/equipment/${created.id}/code?format=png`" target="_blank" class="btn-secondary">
                Code als PNG
            </a>
            <a :href="`/api/v1/equipment/${created.id}/code?format=pdf`" target="_blank" class="btn-secondary">
                Code als PDF
            </a>
            <RouterLink to="/inventar" class="btn-primary">Zur Übersicht</RouterLink>
        </div>
    </div>

    <form v-else class="equipment-form" @submit.prevent="submit">
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
</style>
