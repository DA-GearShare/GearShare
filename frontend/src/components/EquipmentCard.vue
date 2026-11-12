
<script setup lang="ts">
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/AuthStore.ts";
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";
import { ref, onMounted } from "vue";

const props = defineProps<{
    id: string
    name: string
    model: string
    type: string
    selectable?: boolean
    selectDisabled?: boolean
}>();

const authStore = useAuthStore();
const equipmentStore = useEquipmentStore();
const emit = defineEmits(['select', 'deleted']);

const mainPhoto = ref<EquipmentPhoto | null>(null);

async function onDelete() {
    if (!confirm('Wollen Sie das Equipment wirklich löschen?')) return;

    const result = await equipmentStore.deleteEquipment(props.id);

    if (result.ok) {
        emit('deleted');
    } else {
        alert(result.message);
    }
}

interface EquipmentPhoto {
    id: number;
    url: string;
    isMain?: boolean;
    is_main?: boolean;
}

async function loadMainPhoto() {
    try {
        const res = await fetch(
            `/api/v1/equipment/${props.id}/photos`,
            { credentials: "include" }
        );

        if (!res.ok) return;

        const photos: EquipmentPhoto[] = await res.json();

        mainPhoto.value =
            photos.find(photo => photo.isMain === true || photo.is_main === true)
            ?? null;

    } catch (error) {
        console.error("Hauptbild konnte nicht geladen werden:", error);
    }
}

onMounted(loadMainPhoto);
</script>

<template>
    <component :is="selectable ? 'div' : RouterLink" :to="selectable ? undefined : { name: 'equipment-detail', params: { id } }" class="card-link">
        <div class="card card-row">
            <div class="thumb">
                <img v-if="mainPhoto" :src="mainPhoto.url" :alt="`Hauptbild von ${name}`" class="thumb-image"/>

                <div v-else class="thumb-placeholder">
                    <span>Kein Bild</span>
                </div>
            </div>
            <div class="card-info">
                <h3>{{ name }}</h3>
                <p class="meta">{{ model }}</p>
                <div class="tags">
                    <span class="tag">{{ type }}</span>
                </div>
            </div>

            <div v-if="!selectable && authStore.hasRole(['Admin'])" class="card-actions">
                <RouterLink :to="`/inventar/${id}/bearbeiten`" class="btn-secondary card-action" @click.stop>
                    Bearbeiten
                </RouterLink>
                <button type="button" class="btn-secondary card-action delete-button" @click.prevent.stop="onDelete">
                    Löschen
                </button>
            </div>

            <span class="chevron">›</span>
        </div>

        <button
            v-if="selectable"
            class="btn-primary"
            :disabled="selectDisabled"
            @click="emit('select', id)"
        >
            {{ selectDisabled ? 'Bereits zugeordnet' : 'Hinzufügen' }}
        </button>
    </component>
</template>

<style scoped>
.card-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

.card-row {
    display: flex;
    align-items: center;
    gap: var(--space-l);
    min-width: 0;
}

.card-info {
    flex: 1;
    min-width: 0;
}

.card-info h3 {
    overflow-wrap: anywhere;
}

.edit-button {
    flex-shrink: 0;
    gap: var(--space-s);
    margin-left: auto;
}

.card-actions + .chevron {
    margin-left: 0;
}

.card-action {
    min-height: 36px;
    padding: 0 var(--space-l);
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    white-space: nowrap;
    border-radius: var(--radius-tab);
}

.delete-button {
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-color: var(--color-error-text);
}

@media (max-width: 640px) {
    .card-actions {
        flex-direction: column;
        align-items: stretch;
    }
}

.card-info {
    flex: 1;
    min-width: 0;
}

.card-info h3,
.card-info .meta {
    overflow-wrap: anywhere;
    hyphens: auto;
}

.card-info .tag {
    max-width: 100%;
    overflow-wrap: anywhere;
}

@media (max-width: 640px) {
    .thumb {
        width: 72px;
        height: 72px;
    }

    .chevron {
        display: none;
    }
}

/* Tablet und Mobile */
@media (max-width: 600px) {
    .card-row {
        gap: var(--space-s);
    }

    .thumb {
        width: 64px;
        height: 64px;
        flex-shrink: 0;
    }

    .edit-button {
        padding: 0 var(--space-s);
        font-size: 11px;
    }

    .chevron {
        font-size: 22px;
    }
}
</style>
