<script setup lang="ts">
import { useAuthStore } from '@/stores/AuthStore.ts';
const auth = useAuthStore();
</script>

<template>
    <div class="login">
        <div class="section-card login-card">
            <h1>Anmelden</h1>
            <p>Melde dich mit deinem GitHub-Konto an.</p>

            <p v-if="auth.error" class="message-error login-error">{{ auth.error }}</p>

            <a class="btn-primary login-button" href="/api/v1/auth/connect/github">
                Mit GitHub anmelden
            </a>
        </div>
    </div>
</template>

<style scoped>
.login {
    display: flex;
    justify-content: center;
    padding: var(--space-xxl) var(--space-l);
}

.login-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-l);
    width: 100%;
    max-width: 24rem;
    padding: var(--space-xxl);
    text-align: center;
}

.login-card h1,
.login-card p {
    margin: 0;
}

.login-error {
    width: 100%;
    text-align: left;
}

.login-button {
    width: 100%;
}
</style>
