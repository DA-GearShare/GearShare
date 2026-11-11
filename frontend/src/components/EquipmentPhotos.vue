<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import  { useAuthStore } from "@/stores/AuthStore.ts";

const photos = ref([]);
const route = useRoute();
const error = ref<string | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const maxFileSize = 15 * 1024 * 1024;
const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

const authStore = useAuthStore();

onMounted(loadPhotosById);

async function loadPhotosById() {
    try {
        const res = await fetch(`/api/v1/equipment/${route.params.id}/photos`, { credentials: "include" });
        if (!res.ok) {
            error.value = 'Die Fotos konnten nicht geladen werden.';
        } else {
            error.value = null;
            photos.value = await res.json();
            console.log('Geladene Fotos:', photos.value);
        }
    } catch (e) {
        console.log(e);
        error.value = 'Die Fotos konnten nicht geladen werden.';
    }
}

async function uploadPhoto() {
    const file = inputRef.value?.files?.[0];

    if (!file) {
        error.value = 'Bitte wählen Sie zuerst ein Foto aus.';
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
        const res = await fetch(`/api/v1/equipment/${route.params.id}/photos`, { method: 'POST', credentials: 'include', body: formData });

        if (!res.ok) {
            const responseText = await res.text();

            console.error('Upload fehlgeschlagen:');
            console.error('Status:', res.status);
            console.error('Antwort:', responseText);

            error.value = `Upload fehlgeschlagen (${res.status})`;
            return;
        }
        error.value = null;

        await loadPhotosById();

        if (inputRef.value) {
            inputRef.value.value = '';
        }
    } catch (e) {
        console.log(e);
        error.value = 'Das Foto konnte nicht hochgeladen werden.';
    }
}

</script>

<template>
    <div class="equipment-photo">
        <div class="message-error" v-if="error"> {{ error }}</div>
        <div class="equipment-photo__output">
            <ul>
                <li v-for="photo in photos" :key="photo.id">
                    <img class="equipment-photo__img" alt="Equipment-Foto" :src="photo.url" />
                </li>
            </ul>
        </div>
        <div v-if="authStore.hasRole(['Admin'])" class="equipment-photo__input">
            <input ref="inputRef" type="file" class="uploadPhoto" accept="image/jpeg,image/png,image/webp" /><br>
            <button @click="uploadPhoto">Foto Hochladen</button>
        </div>
    </div>
</template>

<style scoped>

</style>
