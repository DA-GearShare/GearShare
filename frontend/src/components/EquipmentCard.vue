<script setup lang="ts">
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/AuthStore.ts";
import { useEquipmentStore } from "@/stores/EquipmentStore.ts";
import { useCompareStore } from '@/stores/CompareStore.ts';
import { ref, onMounted } from "vue";

const props = defineProps<{
    id: string
    name: string
    model: string
    type: string
}>();

const emit = defineEmits(['deleted']);

const authStore = useAuthStore();
const equipmentStore = useEquipmentStore();
const compare = useCompareStore();

interface EquipmentPhoto {
    id: number;
    url: string;
    isMain?: boolean;
    is_main?: boolean;
}

const mainPhoto = ref<EquipmentPhoto | null>(null);

function toggleCompare() {
    if (compare.has(props.id)) {
        compare.remove(props.id);
    } else {
        compare.add(props.id);
    }
}

async function onDelete() {
    if (!confirm('Wollen Sie das Equipment wirklich löschen?')) return;

    const result = await equipmentStore.deleteEquipment(props.id);

    if (result.ok) {
        emit('deleted');
    } else {
        alert(result.message);
    }
}

async function loadMainPhoto() {
    try {
        const res = await fetch(`/api/v1/equipment/${props.id}/photos`, { credentials: "include" });
        if (!res.ok) return;

        const photos: EquipmentPhoto[] = await res.json();
        mainPhoto.value = photos.find(photo => photo.isMain === true || photo.is_main === true) ?? null;
    } catch (error) {
        console.error("Hauptbild konnte nicht geladen werden:", error);
    }
}

onMounted(loadMainPhoto);
</script>

<template>
    <RouterLink :to="{ name: 'equipment-detail', params: { id } }" class="card-link">
        <div class="card card-row">
            <div class="thumb">
                <img v-if="mainPhoto" :src="mainPhoto.url" :alt="`Hauptbild von ${name}`" class="thumb-image" />
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

            <div v-if="authStore.hasRole(['Admin'])" class="card-actions">
                <RouterLink :to="`/inventar/${id}/bearbeiten`" class="btn-secondary card-action" @click.stop>
                    Bearbeiten
                </RouterLink>
                <button type="button" class="btn-secondary card-action delete-button" @click.prevent.stop="onDelete">
                    Löschen
                </button>
            </div>

            <button type="button" class="compare-btn" @click.prevent.stop="toggleCompare">
                {{ compare.has(id) ? '✓' : '⇄' }}
            </button>

            <span class="chevron">›</span>
        </div>
    </RouterLink>
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

.thumb {
    flex-shrink: 0;
    width: 104px;
    height: 104px;
    overflow: hidden;
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumb-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--color-muted);
    font-size: var(--text-help-size);
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

.card-action {
    min-height: 36px;
    padding: 0 var(--space-l);
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    white-space: nowrap;
    border-radius: var(--radius-tab);
}

.card-actions {
    display: flex;
    flex-shrink: 0;
    gap: var(--space-s);
}

.delete-button {
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-color: var(--color-error-text);
}

.compare-btn {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    font-size: 18px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-tab);
    cursor: pointer;
}

.chevron {
    flex-shrink: 0;
    font-size: 28px;
    color: var(--color-text-primary);
}

@media (max-width: 640px) {
    .card-row {
        gap: var(--space-s);
    }

    .thumb {
        width: 64px;
        height: 64px;
    }

    .card-actions {
        flex-direction: column;
        align-items: stretch;
        gap: var(--space-s);
    }

    .card-action {
        width: 100%;
        padding: 0 var(--space-s);
        font-size: 11px;
    }

    .chevron {
        display: none;
    }
}
</style>
