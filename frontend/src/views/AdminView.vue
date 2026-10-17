<script setup lang="ts">
import { useUsersStore } from '@/stores/UsersStore.ts';
import { onMounted } from 'vue';

const usersStore = useUsersStore();

onMounted(usersStore.fetchUsers);
</script>

<template>
    <h1>Admin Seite</h1>

    <p v-if="usersStore.isLoading">Lädt ...</p>
    <p v-else-if="usersStore.error">{{ usersStore.error }}</p>
    <p v-else-if="usersStore.users.length === 0">Keine Benutzer vorhanden.</p>
    <ul v-else>
        <li v-for="u in usersStore.users" :key="u.id">
            {{ u.displayname }} ({{ u.email }})
            Aktuelle Rolle:
            <p v-if="u.isAdmin">Admin</p>
            <p v-else-if="u.isTeacher">Lehrer</p>
            <p v-else>Benutzer</p>
            <button v-if="!u.isAdmin" @click="usersStore.setAdmin(u, true)">
                Zum Admin machen
            </button>
            <button v-else @click="usersStore.setAdmin(u, false)">
                Admin entziehen
            </button>
        </li>
    </ul>
</template>

<style scoped>

</style>
