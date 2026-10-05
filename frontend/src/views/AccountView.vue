<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/AuthStore.ts';

const auth = useAuthStore();
const router = useRouter();

function logout() {
    auth.logout();
    router.push('/login');
}
</script>

<template>
    <h1>Accountdetails</h1><br>
    <button class="btn-primary" @click="logout">Logout</button>
</template>

<style scoped>

</style>
