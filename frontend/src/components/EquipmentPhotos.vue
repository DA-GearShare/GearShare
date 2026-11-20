
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/AuthStore.ts';

interface EquipmentPhoto {
    id: number;
    url: string;
    isMain: boolean;
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
const equipment = ref<any>(null);

const isAdmin = authStore.hasRole(['Admin']);




onMounted(()=>{
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

        const res = await fetch(
            `/api/v1/equipment/${equipmentId}/photos`,
            { credentials: 'include' }
        );

        if (!res.ok) {
            throw new Error('Die Fotos konnten nicht geladen werden.');
        }

        photos.value = await res.json();
        console.log('Geladene Fotos:', photos.value);
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

            <div v-else-if="photos.length === 0" class="gallery">
                <div v-for="n in 4" :key="n" class="placeholder thumb">Bild {{ n }}</div>
                <div class="placeholder main">Bildplatzhalter</div>
            </div>

            <ul v-else class="gallery">
                <li v-for="photo in photos" :key="photo.id" class="equipment-photo__item" :class="{ 'equipment-photo__item--main': photo.isMain }">
                    <img class="equipment-photo__img" alt="Equipment-Foto" :src="photo.url"/>

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
        </div>

        <div v-if="equipment" class="info">
            <p class="type">{{ equipment.type }}</p>
            <h1 class="name">{{ equipment.model }}</h1>
            <span class="tag">{{ equipment.name }}</span>

            <template v-if="equipment.description">
                <h3 class="section-title">Beschreibung</h3>
                <p class="description">{{ equipment.description }}</p>
            </template>

            <div class="facts">
                <div class="fact">
                    <p class="fact-label">Inventarnr.</p>
                    <p class="fact-value">{{ equipment.code }}</p>
                </div>
                <div class="fact">
                    <p class="fact-label">Status</p>
                    <p class="fact-value">{{ available === false ? 'Nicht verfügbar' : 'Verfügbar' }}</p>
                </div>
            </div>
        </div>

            <p>
                {{ photos.length }} / {{ maxPhotos }} Fotos
            </p>
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
    max-width: 600px;
    padding: 16px;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #E8EAEE;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);
}

.message-error {
    margin-bottom: 16px;
    padding: 12px 16px;
    font-size: 14px;
    line-height: 21px;
    color: #B91C1C;
    background: #FEECEC;
    border-left: 3px solid #B91C1C;
    border-radius: 12px;
}

.hint {
    margin: 0;
    font-size: 13px;
    line-height: 18px;
    color: #6B7280;
}

.gallery {
    display: grid;
    grid-template-columns: 88px minmax(0, 1fr);
    grid-auto-flow: row dense;
    gap: 12px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.equipment-photo__item,
.gallery .thumb {
    grid-column: 1;
    position: relative;
    min-width: 0;
}

.equipment-photo__item--main,
.gallery .main {
    grid-column: 2;
    grid-row: 1 / span 4;
    align-self: stretch;
    contain: size;
    min-height: 180px;
}

.equipment-photo__img {
    display: block;
    width: 100%;
    aspect-ratio: 5 / 4;
    object-fit: cover;
    background: #F3F4F6;
    border-radius: 12px;
}

.equipment-photo__item--main .equipment-photo__img {
    height: 100%;
    aspect-ratio: auto;
    border-radius: 18px;
}

.gallery .placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    line-height: 18px;
    color: #6B7280;
    background: linear-gradient(145deg, #F3F4F6, #E9EDF1);
}

.gallery .thumb {
    width: auto;
    height: auto;
    aspect-ratio: 5 / 4;
    border-radius: 12px;
}

.gallery .main {
    width: auto;
    height: auto;
    border-radius: 18px;
}

.main-label {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 2px 10px;
    font-size: 12px;
    line-height: 18px;
    font-weight: 700;
    color: #4F6D47;
    background: #E1EDDC;
    border-radius: 999px;
}

.equipment-photo__actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 4px;
}

.info {
    margin-top: 16px;
    margin-left: 100px;
}

.type {
    margin: 0;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    color: #2456C8;
}

.name {
    margin: 4px 0 8px;
    font-size: 24px;
    line-height: 32px;
    font-weight: 700;
    color: #12273C;
}

.info .tag {
    display: inline-block;
    padding: 4px 12px;
    font-size: 13px;
    line-height: 18px;
    color: #374151;
    background: #F3F4F6;
    border-radius: 999px;
}

.section-title {
    margin: 16px 0 4px;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    color: #111827;
}

.description {
    margin: 0;
    font-size: 14px;
    line-height: 21px;
    color: #374151;
}

.facts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 16px;
}

.fact {
    padding: 12px 16px;
    background: #F7F8FA;
    border-radius: 12px;
}

.fact-label {
    margin: 0 0 2px;
    font-size: 12px;
    line-height: 18px;
    color: #6B7280;
}

.fact-value {
    margin: 0;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    color: #111827;
}

.equipment-photo__input {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    margin-top: 24px;
    padding: 16px;
    background: #F7F8FA;
    border: 1px solid #E5E7EB;
    border-radius: 18px;
}

.equipment-photo__input input[type="file"] {
    width: 100%;
    min-height: 44px;
    padding: 8px;
    font-size: 14px;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
}

.equipment-photo button {
    min-height: 40px;
    padding: 4px 8px;
    font-family: inherit;
    font-size: 12px;
    line-height: 16px;
    font-weight: 700;
    color: #12273C;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    cursor: pointer;
}

.equipment-photo button:hover:not(:disabled) {
    background: #F3F4F6;
}

.equipment-photo button:disabled {
    color: #6B7280;
    background: #E9EDF1;
    cursor: not-allowed;
}

.equipment-photo__input button {
    min-height: 48px;
    padding: 0 16px;
    font-size: 15px;
    line-height: 22px;
    color: #FFFFFF;
    background: #12273C;
    border-color: #12273C;
    border-radius: 14px;
}

.equipment-photo__input button:hover:not(:disabled) {
    background: #2456C8;
    border-color: #2456C8;
}

.equipment-photo .delete-button {
    color: #B91C1C;
    background: #FEECEC;
    border-color: #FEECEC;
}

.equipment-photo .delete-button:hover:not(:disabled) {
    border-color: #B91C1C;
}

.equipment-photo button:focus-visible,
.equipment-photo input:focus-visible {
    outline: 2px solid #2456C8;
    outline-offset: 3px;
}

@media (max-width: 639px) {
    .equipment-photo {
        max-width: none;
    }

    .gallery {
        grid-template-columns: 72px minmax(0, 1fr);
    }

    .info {
        margin-left: 84px;
    }
}

@media (max-width: 400px) {
    .info {
        margin-left: 0;
    }
}
</style>
