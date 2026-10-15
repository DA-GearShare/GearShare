<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/AuthStore.ts';

const auth = useAuthStore();
const router = useRouter();

async function logout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <h1>Accountdetails</h1><br>

    <p v-if="auth.error">{{ auth.error }}</p>
    <div v-if="auth.user">
        <p>Name: {{ auth.user.payload.displayname }}</p>
        <p>E-Mail: {{ auth.user.payload.email }}</p>
        <p>Rolle: {{ auth.role }}</p>
    </div>
    <br>
    <button class="btn-primary" @click="logout">Logout</button>
</template>

<style scoped>

</style>
