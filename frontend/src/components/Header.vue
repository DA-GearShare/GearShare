<script setup lang="ts">
import { User, ShoppingCart } from 'lucide-vue-next';
import { useAuthStore } from "@/stores/AuthStore.ts";

const authStore = useAuthStore();
</script>

<template>
    <header class="header">
        <RouterLink to="/inventar" class="logo-link" aria-label="Zum Inventar">
            <img src="@/assets/logo.svg" alt="GearShare" class="logo" />
        </RouterLink>

        <nav class="nav">
            <RouterLink to="/inventar" class="nav-link">Inventar</RouterLink>
            <RouterLink to="/laborzeit" class="nav-link">Laborzeit</RouterLink>
            <RouterLink to="/anleitung" class="nav-link">Anleitung</RouterLink>
            <RouterLink v-if="authStore.hasRole(['Admin'])" to="/admin" class="nav-link">Admin</RouterLink>
        </nav>

        <div class="actions">
            <button type="button" class="icon-button" aria-label="Reservierungen">
                <ShoppingCart />
            </button>

            <RouterLink to="/account" class="icon-button" aria-label="Mein Konto">
                <User />
            </RouterLink>
        </div>
    </header>
</template>

<style scoped>
.header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    padding: var(--space-m) var(--space-xl);
    background: var(--color-background-areas);
    border-bottom: 1px solid var(--color-border);
}

.logo-link {
    justify-self: start;
}

.logo {
    display: block;
    height: 40px;
    max-width: 180px;
}

.nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-xl);
}

.nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 var(--space-l);
    border-radius: var(--radius-tab);
    white-space: nowrap;
}

.nav-link:hover {
    background: var(--color-border);
}

.nav-link.router-link-active {
    color: var(--color-surface);
    background: var(--color-navy);
}

.actions {
    display: flex;
    justify-self: end;
    gap: var(--space-s);
}

.icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    padding: 0;
    color: var(--color-text-primary);
    background: none;
    border: none;
    border-radius: var(--radius-tab);
    cursor: pointer;
}

.icon-button:hover {
    background: var(--color-border);
}

.icon-button svg {
    width: 24px;
    height: 24px;
}

/* Tablet und Mobile: Navigation in eigener Zeile */
@media (max-width: 900px) {
    .header {
        grid-template-columns: 1fr auto;
        padding: var(--space-m) var(--space-l);
    }

    .logo {
        height: 32px;
    }

    .nav {
        grid-column: 1 / -1;
        grid-row: 2;
        flex-wrap: wrap;
        gap: var(--space-s);
        margin-top: var(--space-m);
    }

    .nav-link {
        padding: 0 var(--space-m);
    }
}
</style>
