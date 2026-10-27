<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router'
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";

const router = useRouter();
const equipmentStore = useEquipmentStore();

const name = ref('');
const location = ref('');
const type = ref('');
const model = ref('');
const code = ref('');

async function submit() {
    const payload: Record<string, string> = {
        name: name.value.trim(),
        location: location.value.trim(),
        type: type.value.trim(),
        model: model.value.trim(),
    };

    if (code.value.trim() !== '') {
        payload.code = code.value.trim();
    }

    console.log('Zu speichern:', payload);
    const ok = await equipmentStore.createEquipment(payload);
    if (ok) {
        router.push('/inventar');
    }
}

</script>

<template>
    <h1>Equipment hinzufügen</h1>

    <form class="equipment-form" @submit.prevent="submit">
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

        <button type="submit" class="btn-primary">Speichern</button>
    </form>
</template>

<style scoped>
.equipment-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 480px;
    margin-top: 1.5rem;
}

label {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

input {
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    color: var(--color-text-primary);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 6px;
}

.hint {
    font-size: 0.8125rem;
    color: var(--color-muted);
}
</style>
