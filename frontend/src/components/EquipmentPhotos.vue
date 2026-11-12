
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/AuthStore.ts';

interface EquipmentPhoto {
    id: number;
    url: string;
    isMain: boolean;
}

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

const isAdmin = authStore.hasRole(['Admin']);

onMounted(loadPhotosById);

async function loadPhotosById() {
    try {
        loading.value = true;

        const res = await fetch(
            `/api/v1/equipment/${equipmentId}/photos`,
            { credentials: 'include' }
        );

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

async function uploadPhoto() {
    const file = inputRef.value?.files?.[0];

    if (!file) {
        error.value = 'Bitte wählen Sie zuerst ein Foto aus.';
        return;
    }

    if (photos.value.length >= maxPhotos) {
        error.value = 'Es können maximal 5 Fotos hochgeladen werden.';
        return;
    }

    if (!allowedTypes.includes(file.type)) {
        error.value = 'Erlaubt sind nur JPG, PNG und WebP.';
        return;
    }

    if (file.size > maxFileSize) {
        error.value = 'Das ausgewählte Foto ist zu groß (max. 15 MB).';
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await fetch(
            `/api/v1/equipment/${equipmentId}/photos`,
            {
                method: 'POST',
                credentials: 'include',
                body: formData
            }
        );

        if (!res.ok) {
            const responseText = await res.text();
            console.error('Upload fehlgeschlagen:', res.status, responseText);

            error.value = res.status === 413
                ? 'Das Foto ist zu groß.'
                : 'Das Foto konnte nicht hochgeladen werden.';

            return;
        }

        error.value = null;

        await loadPhotosById();

        if (inputRef.value) {
            inputRef.value.value = '';
        }

    } catch (e) {
        console.error(e);
        error.value = 'Das Foto konnte nicht hochgeladen werden.';
    }
}

async function setMainPhoto(photoId: number) {
    try {
        const res = await fetch(
            `/api/v1/equipment/${equipmentId}/photos/${photoId}`,
            {
                method: 'PATCH',
                credentials: 'include'
            }
        );

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
            `/api/v1/equipment/${equipmentId}/photos/${photoId}`,
            {
                method: 'DELETE',
                credentials: 'include'
            }
        );

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
    <div class="equipment-photo">

        <div v-if="error" class="message-error">
            {{ error }}
        </div>

        <div class="equipment-photo__output">

            <p v-if="loading">
                Fotos werden geladen...
            </p>

            <p v-else-if="photos.length === 0">
                Noch keine Fotos vorhanden.
            </p>

            <ul v-else>
                <li v-for="photo in photos" :key="photo.id" class="equipment-photo__item">
                    <img class="equipment-photo__img" :class="{ 'equipment-photo__img--main': photo.isMain }" alt="Equipment-Foto" :src="photo.url"/>

                    <span v-if="photo.isMain" class="main-label">
                        Hauptbild
                    </span>

                    <div v-if="isAdmin" class="equipment-photo__actions">

                        <button v-if="!photo.isMain" @click="setMainPhoto(photo.id)">
                            Als Hauptbild festlegen
                        </button>

                        <button @click="deletePhoto(photo.id)" class="delete-button">
                            Foto löschen
                        </button>

                    </div>
                </li>
            </ul>

            <p>
                {{ photos.length }} / {{ maxPhotos }} Fotos
            </p>

        </div>

        <div v-if="isAdmin" class="equipment-photo__input">
            <template v-if="photos.length < maxPhotos">

                <input ref="inputRef" type="file" class="uploadPhoto" accept="image/jpeg,image/png,image/webp"/>

                <button @click="uploadPhoto" :disabled="loading">
                    Foto hochladen
                </button>

                <p>
                    Erlaubt: JPG, PNG, WebP (max. 15 MB).
                    Maximal 5 Fotos pro Equipment.
                </p>

            </template>

            <p v-else>
                Maximale Anzahl von 5 Fotos erreicht.
            </p>
        </div>

    </div>
</template>

<style scoped>

.equipment-photo {
    margin-top: var(--space-xl);
    padding: var(--space-l);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-section);
    box-shadow: var(--shadow-card);
}

.message-error {
    margin-bottom: var(--space-l);
    padding: var(--space-m) var(--space-l);
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-left: 3px solid var(--color-error-text);
    border-radius: var(--radius-tab);
}

.equipment-photo__output {
    margin-bottom: var(--space-l);
}

.equipment-photo__output ul {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: var(--space-l);
    padding: 0;
    margin: var(--space-l) 0;
    list-style: none;
}

.equipment-photo__item {
    position: relative;
    min-width: 0;
    padding: var(--space-s);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
}

.equipment-photo__img {
    display: block;
    width: 100%;
    height: 160px;
    object-fit: cover;
    background: var(--color-soft-surface);
    border: 2px solid transparent;
    border-radius: var(--radius-tab);
}

.equipment-photo__img--main {
    border-color: var(--color-available-accent);
}

.main-label {
    display: inline-flex;
    align-items: center;
    margin-top: var(--space-s);
    padding: var(--space-xs) var(--space-m);
    color: var(--color-available-text);
    background: var(--color-available-bg);
    border-radius: var(--radius-pill);
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    font-weight: var(--font-bold);
}

.equipment-photo__actions {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
    margin-top: var(--space-m);
}

.equipment-photo button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: var(--space-s) var(--space-m);
    font-family: var(--font-family);
    font-size: var(--text-body-size);
    font-weight: var(--font-bold);
    color: var(--color-navy);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-button);
    cursor: pointer;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.equipment-photo button:hover:not(:disabled) {
    background: var(--color-soft-surface);
    border-color: var(--color-accent);
}

.equipment-photo button:disabled {
    color: var(--color-muted);
    background: var(--color-soft-surface);
    cursor: not-allowed;
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
    max-width: 100%;
    min-height: 44px;
    padding: var(--space-s);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-tab);
}

.equipment-photo__input button {
    color: var(--color-surface);
    background: var(--color-navy);
    border: 1px solid var(--color-navy);
}

.equipment-photo__input button:hover:not(:disabled) {
    color: var(--color-surface);
    background: var(--color-accent);
    border-color: var(--color-accent);
}

.equipment-photo .delete-button {
    color: var(--color-error-text);
    background: var(--color-error-bg);
    border-color: var(--color-error-bg);
}

.equipment-photo .delete-button:hover:not(:disabled) {
    border-color: var(--color-error-text);
}

.equipment-photo p {
    margin: 0 0 var(--space-s);
    font-size: var(--text-meta-size);
    line-height: var(--text-meta-line);
    color: var(--color-muted);
}

@media (max-width: 600px) {
    .equipment-photo {
        padding: var(--space-m);
    }

    .equipment-photo__output ul {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--space-s);
    }

    .equipment-photo__img {
        height: 120px;
    }

    .equipment-photo__actions button {
        font-size: var(--text-help-size);
        padding: var(--space-s);
    }
}

</style>
