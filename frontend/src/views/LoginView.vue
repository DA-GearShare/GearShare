<script setup lang="ts">
import { useAuthStore } from '@/stores/AuthStore.ts';
const auth = useAuthStore();

</script>

<template>
    <div class="login">
        <div class="card">
            <h1>Anmelden</h1>
            <p>Melde dich mit deinem GitHub-Konto an.</p>

            <p v-if="auth.error" class="error">{{ auth.error }}</p>

            <a class="login-button" href="/api/v1/auth/connect/github">
                Login with GitHub
            </a>
        </div>
    </div>
</template>

<style scoped>
.login {
  display: flex;
  justify-content: center;
  padding: 3rem 1rem;
}

.card {
  width: 100%;
  max-width: 24rem;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  text-align: center;
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
}

h1 {
  font-size: 1.5rem;
  font-weight: 600;
}

p {
  color: var(--color-muted);
}

.login-button {
  width: 100%;
  padding: 0.75rem 1rem;
  background-color: var(--color-navy);
  color: var(--color-surface);
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
}

.login-button:hover {
  opacity: 0.9;
}

.error {
  width: 100%;
  padding: 0.75rem 1rem;
  color: #b42318;
  background-color: #fef3f2;
  border: 1px solid #fecdca;
  border-radius: 8px;
}
</style>
