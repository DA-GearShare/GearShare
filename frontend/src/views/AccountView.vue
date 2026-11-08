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
    <h1>Accountdetails</h1>

    <p v-if="auth.error" class="message-error">{{ auth.error }}</p>

    <div v-if="auth.user" class="section-card account-card">
        <dl class="account-list">
            <div>
                <dt>Name</dt>
                <dd>{{ auth.user.displayname }}</dd>
            </div>
            <div>
                <dt>E-Mail</dt>
                <dd>{{ auth.user.email }}</dd>
            </div>
            <div>
                <dt>Rolle</dt>
                <dd><span class="tag">{{ auth.role }}</span></dd>
            </div>
        </dl>
    </div>

    <div class="account-actions">
        <button class="btn-primary" @click="logout">Logout</button>
    </div>
</template>

<style scoped>
.account-card {
    max-width: 480px;
    margin-top: var(--space-l);
}

.account-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-l);
    margin: 0;
}

.account-list dt {
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    font-weight: var(--font-bold);
    color: var(--color-muted);
}

.account-list dd {
    margin: var(--space-xs) 0 0;
    color: var(--color-text-primary);
}

.account-actions {
    margin-top: var(--space-xl);
}
</style>
