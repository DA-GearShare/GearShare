<script setup lang="ts">
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";

const props = defineProps<{
    id: string;
    title: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const equipmentStore = useEquipmentStore();

function deleteEquipment() {
    equipmentStore.deleteEquipment(props.id);
    if (equipmentStore.fetchEquipmentById(props.id) === null) {
        alert("Equipment erfolgreich entfernt!");
    }
}
</script>

<template>
    <div class="overlay" @click.self="emit('close')">
        <div class="card modal">
            <h3>{{ title }}</h3>
            <p class="meta">Wollen Sie das Equipment löschen?</p>

            <form class="manual" @submit.prevent="deleteEquipment">
                <button type="submit" class="btn-primary" @click="deleteEquipment">Löschen</button>
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
