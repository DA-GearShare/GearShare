<script setup lang="ts">
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/AuthStore.ts";
import { useCompareStore } from '@/stores/CompareStore.ts';
import { Pencil, Trash2 } from "lucide-vue-next";
import { ref, onMounted } from "vue";
import DeleteModal from "@/modals/DeleteModal.vue";

const props = defineProps<{
    id: string
    name: string
    model: string
    type: string
}>();

const authStore = useAuthStore();

const compare = useCompareStore();
const showDelete = ref(false);

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

async function loadMainPhoto() {
    try {
        const res = await fetch(`/api/v1/equipment/${props.id}/photos`, { credentials: "include" });
        if (!res.ok) return;

        const photos: EquipmentPhoto[] = await res.json();
        mainPhoto.value = photos.find(photo => {
            return photo.isMain === true || photo.is_main === true;
        }) ?? null;
    } catch (error) {
        console.error("Hauptbild konnte nicht geladen werden:", error);
    }
}

onMounted(loadMainPhoto);

function DeletePopUp() {
    showDelete.value = true;
}
function closeDelete() {
    showDelete.value = false;
}
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
                <RouterLink :to="`/inventar/${id}/bearbeiten`" class="icon-button" aria-label="Equipment bearbeiten" title="Bearbeiten" @click.stop>
                    <Pencil />
                </RouterLink>
                <button type="button" class="icon-button delete-button" aria-label="Equipment löschen" title="Löschen" @click.prevent.stop="DeletePopUp">
                    <Trash2 />
                </button>
            </div>

            <button type="button" class="compare-btn" @click.prevent.stop="toggleCompare">
                {{ compare.has(id) ? '✓' : '⇄' }}
            </button>

            <span class="chevron">›</span>
        </div>
    </RouterLink>
    <DeleteModal
        v-if="showDelete"
        title="Equipment Löschen"
        :id="props.id"
        @close="closeDelete"
    />
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

.card-actions {
    display: flex;
    flex-shrink: 0;
    gap: var(--space-s);
}

.card-actions + .chevron {
    margin-left: 0;
}

.icon-button {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    padding: 0;
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border: none;
    border-radius: var(--radius-s);
    cursor: pointer;
    text-decoration: none;
}

.icon-button svg {
    width: 16px;
    height: 16px;
}

.delete-button {
    color: var(--color-error-text);
    background: var(--color-error-bg);
}

.delete-button:hover {
    background: #fbdada;
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
