<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import  { useAuthStore } from "@/stores/AuthStore.ts";

const photos = ref([]);
const route = useRoute();
const error = ref();

const authStore = useAuthStore();

onMounted(loadPhotosById);

async function loadPhotosById() {
    try {
        const res = await fetch(`/api/v1/equipment/${route.params.id}/photos`);
        if (!res.ok) {
            error.value = 'Die Fotos konnten nicht geladen werden.';
        } else {
            error.value = null;
            photos.value = await res.json();
        }
    } catch (e) {
        error.value = 'Die Fotos konnten nicht geladen werden. Error: ' + e;
    }
}

async function uploadPhoto() {
    try {
        const formData = new FormData();
        formData.append('file', file);
        await fetch(`/api/v1/equipment/${route.params.id}/photos`, { method: 'POST', credentials: 'include', body: formData });
    } catch (e) {
        error.value = 'Das Foto konnte nicht heruntergeladen werden. Error: ' + e;
    }
}

</script>

<template>
    <div class="equipment-photo">
        <div class="equipment-photo__output">
            <li v-for="photo in photos" :key="photo.id">
                <img class="equipment-photo__img" :src="photo.url" />
            </li>
        </div>
        <div v-if="authStore.hasRole(['Admin'])" class="equipment-photo__input">
            <input type="file" class="uploadPhoto" /><br>
            <button @click="uploadPhoto()">Foto Hochladen</button>
        </div>
    </div>
</template>

<style scoped>

</style>
