<script setup lang="ts">
const emit = defineEmits(['search']);
const search = (e: Event) => {
    emit('search', (e.target as HTMLInputElement).value);
};
</script>

<template>
    <form class="search" @submit.prevent>
        <svg class="search-icon"
             width="18"
             height="18"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="M20 20l-3.5-3.5" />
        </svg>
        <input type="text" placeholder="Suche" aria-label="Suche" @input="search">
    </form>
</template>

<style scoped>
.search {
    position: relative;
    width: 100%;
}

.search-icon {
    position: absolute;
    top: 50%;
    left: var(--space-l);
    transform: translateY(-50%);
    color: var(--color-text-primary);
    pointer-events: none;
}

.search input {
    width: 100%;
    min-height: 52px;
    padding: 0 var(--space-l) 0 44px;
    background: var(--color-surface);
    border-radius: var(--radius-pill);
}
</style>
