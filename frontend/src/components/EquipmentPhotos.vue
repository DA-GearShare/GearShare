<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/AuthStore.ts';

interface EquipmentPhoto {
    id: number;
    url: string;
    isMain: boolean;
}

interface Equipment {
    type: string;
    model: string;
    name: string;
    description?: string;
    code: string;
}

defineProps<{
    available: boolean;
}>();

const photos = ref<EquipmentPhoto[]>([]);
const route = useRoute();
const authStore = useAuthStore();

const error = ref<string | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const loading = ref(false);

const maxFileSize = 15 * 1024 * 1024;
const maxPhotos = 5;
const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

const equipmentId = route.params.id;
const equipment = ref<Equipment | null>(null);

const isAdmin = authStore.hasRole(['Admin']);

const selectedId = ref<number | null>(null);

const selectedPhoto = computed(() => {
    return photos.value.find(p => {
        return p.id === selectedId.value;
    })
        ?? photos.value.find(p => {
            return p.isMain;
        })
        ?? photos.value[0]
        ?? null;
});

const remainingSlots = computed(() => {
    return maxPhotos - photos.value.length;
});

onMounted(() => {
    loadPhotosById();
    loadEquipment();
});

async function loadEquipment() {
    const res = await fetch(`/api/v1/equipment/${equipmentId}`, { credentials: 'include' });
    if (!res.ok) return;
    equipment.value = await res.json();
}

async function loadPhotosById() {
    try {
        loading.value = true;

        const res = await fetch(`/api/v1/equipment/${equipmentId}/photos`, { credentials: 'include' });

        if (!res.ok) {
            throw new Error('Die Fotos konnten nicht geladen werden.');
        }

        photos.value = await res.json();
        error.value = null;

    } catch (e) {
        console.error(e);
        error.value = 'Die Fotos konnten nicht geladen werden.';
    } finally {
        loading.value = false;
    }
}

async function uploadPhotos() {
    const files = Array.from(inputRef.value?.files ?? []);

    if (files.length === 0) {
        error.value = 'Bitte wählen Sie zuerst mindestens ein Foto aus.';
        return;
    }

    if (files.length > remainingSlots.value) {
        error.value = `Sie können noch ${remainingSlots.value} Foto(s) hochladen.`;
        return;
    }

    for (const file of files) {
        if (!allowedTypes.includes(file.type)) {
            error.value = `${file.name}: Erlaubt sind nur JPG, PNG und WebP.`;
            return;
        }
        if (file.size > maxFileSize) {
            error.value = `${file.name} ist zu groß (max. 15 MB).`;
            return;
        }
    }

    loading.value = true;
    let failed = 0;

    for (const file of files) {
        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch(`/api/v1/equipment/${equipmentId}/photos`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            if (!res.ok) {
                console.error('Upload fehlgeschlagen:', res.status, await res.text());
                failed++;
            }
        } catch (e) {
            console.error(e);
            failed++;
        }
    }

    if (inputRef.value) {
        inputRef.value.value = '';
    }

    await loadPhotosById();

    if (failed > 0) {
        error.value = `${failed} von ${files.length} Fotos konnten nicht hochgeladen werden.`;
    }
}

async function setMainPhoto(photoId: number) {
    try {
        const res = await fetch(`/api/v1/equipment/${equipmentId}/photos/${photoId}`, { method: 'PATCH', credentials: 'include' });

        if (!res.ok) {
            throw new Error('Das Hauptbild konnte nicht geändert werden.');
        }

        await loadPhotosById();

    } catch (e) {
        console.error(e);
        error.value = 'Das Hauptbild konnte nicht geändert werden.';
    }
}

async function deletePhoto(photoId: number) {
    if (!confirm('Möchten Sie dieses Foto wirklich löschen?')) {
        return;
    }

    try {
        const res = await fetch(
            `/api/v1/equipment/${equipmentId}/photos/${photoId}`, { method: 'DELETE', credentials: 'include' });

        if (!res.ok) {
            throw new Error('Das Foto konnte nicht gelöscht werden.');
        }

        error.value = null;
        await loadPhotosById();

    } catch (e) {
        console.error(e);
        error.value = 'Das Foto konnte nicht gelöscht werden.';
    }
}
</script>

<template>
    <div class="equipment-photo section-card">

        <div v-if="error" class="message-error">
            {{ error }}
        </div>

        <div class="equipment-photo__output">

            <p v-if="loading && photos.length === 0" class="meta">
                Fotos werden geladen...
            </p>

            <div v-else-if="photos.length === 0" class="placeholder meta">
                Noch keine Fotos vorhanden
            </div>

            <div v-else class="gallery">
                <div class="gallery__thumbs">
                    <button
                        v-for="photo in photos"
                        :key="photo.id"
                        type="button"
                        class="gallery__thumb"
                        :class="{ 'gallery__thumb--active': photo.id === selectedPhoto?.id }"
                        @click="selectedId = photo.id"
                    >
                        <img :src="photo.url" alt="Equipment-Foto Vorschau"/>
                    </button>
                </div>

                <div class="gallery__main">
                    <div class="main-frame">
                        <img :src="selectedPhoto?.url" alt="Equipment-Foto"/>
                        <span v-if="selectedPhoto?.isMain" class="tag tag-available main-label">
                            Hauptbild
                        </span>
                    </div>

                    <div v-if="isAdmin && selectedPhoto" class="equipment-photo__actions">
                        <button
                            v-if="!selectedPhoto.isMain"
                            type="button"
                            class="btn-secondary"
                            @click="setMainPhoto(selectedPhoto.id)"
                        >
                            Als Hauptbild
                        </button>

                        <button
                            type="button"
                            class="btn-secondary delete-button"
                            @click="deletePhoto(selectedPhoto.id)"
                        >
                            Löschen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="equipment" class="info">
            <p class="eyebrow">{{ equipment.type }}</p>
            <h1 class="name">{{ equipment.model }}</h1>
            <span class="tag">{{ equipment.name }}</span>

            <template v-if="equipment.description">
                <h3 class="section-title">Beschreibung</h3>
                <p>{{ equipment.description }}</p>
            </template>

            <div class="facts">
                <div class="fact">
                    <p class="meta">Inventarnr.</p>
                    <p class="fact-value">{{ equipment.code }}</p>
                </div>
                <div class="fact">
                    <p class="meta">Status</p>
                    <p class="fact-value">{{ available === false ? 'Nicht verfügbar' : 'Verfügbar' }}</p>
                </div>
            </div>
        </div>

        <p class="meta photo-count">
            {{ photos.length }} / {{ maxPhotos }} Fotos
        </p>

        <div v-if="isAdmin" class="equipment-photo__input">
            <template v-if="photos.length < maxPhotos">

                <input
                    ref="inputRef"
                    type="file"
                    class="uploadPhoto"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                />

                <button class="btn-primary" @click="uploadPhotos" :disabled="loading">
                    Foto(s) hochladen
                </button>

                <p class="hint">
                    Erlaubt: JPG, PNG, WebP (max. 15 MB).
                    Noch {{ remainingSlots }} Foto(s) möglich.
                </p>

            </template>

            <p v-else class="hint">
                Maximale Anzahl von 5 Fotos erreicht.
            </p>
        </div>

    </div>
</template>

<style scoped>
.equipment-photo {
    --thumb-col: 72px;
    max-width: 600px;
    box-shadow: var(--shadow-card);
}

.message-error {
    margin-bottom: var(--space-l);
}

.gallery {
    display: grid;
    grid-template-columns: var(--thumb-col) minmax(0, 1fr);
    gap: var(--space-m);
}

.gallery__thumbs {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
    align-self: start;
}

.gallery__thumb {
    padding: 0;
    overflow: hidden;
    aspect-ratio: 4 / 3;
    background: var(--color-soft-surface);
    border: 2px solid transparent;
    border-radius: var(--radius-s);
    opacity: 0.7;
    cursor: pointer;
}

.gallery__thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery__thumb--active {
    border-color: var(--color-accent);
    opacity: 1;
}

.main-frame {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: var(--color-soft-surface);
    border-radius: var(--radius-card);
}

.main-frame img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.main-label {
    position: absolute;
    top: var(--space-s);
    left: var(--space-s);
}

.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 4 / 3;
    margin: 0;
    background: var(--color-soft-surface);
    border-radius: var(--radius-card);
}

.equipment-photo__actions {
    display: flex;
    gap: var(--space-s);
    margin-top: var(--space-s);
}

.equipment-photo__actions button {
    min-height: 40px;
    padding: 0 var(--space-m);
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    border-radius: var(--radius-s);
}

.delete-button {
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-color: var(--color-error-bg);
}

.delete-button:hover:not(:disabled) {
    background: var(--color-error-bg);
    border-color: var(--color-error-text);
}

.info {
    margin-top: var(--space-l);
    margin-left: calc(var(--thumb-col) + var(--space-m));
}

.name {
    margin: var(--space-xs) 0 var(--space-s);
}

.section-title {
    margin: var(--space-l) 0 var(--space-xs);
    color: var(--color-text-primary);
}

.facts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-m);
    margin-top: var(--space-l);
}

.fact {
    padding: var(--space-m) var(--space-l);
    background: var(--color-background);
    border-radius: var(--radius-tab);
}

.fact .meta {
    margin: 0 0 2px;
}

.fact-value {
    margin: 0;
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
}

.photo-count {
    margin-top: var(--space-l);
}

.equipment-photo__input {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-m);
    margin-top: var(--space-xl);
    padding: var(--space-l);
    background: var(--color-background);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-card);
}

.equipment-photo__input input[type="file"] {
    width: 100%;
}

.equipment-photo__input .hint {
    margin: 0;
}

.btn-primary:hover:not(:disabled) {
    background: var(--color-accent);
    border-color: var(--color-accent);
}

@media (max-width: 639px) {
    .equipment-photo {
        --thumb-col: 60px;
        max-width: none;
    }
}

@media (max-width: 400px) {
    .info {
        margin-left: 0;
    }
}
</style>
