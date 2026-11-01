<script setup lang="ts">
defineProps({
    page: { type: Number, required: true },
    total_pages: { type: Number, required: true },
});
const emit = defineEmits(['changePage']);
</script>

<template>
    <nav v-if="total_pages > 1" class="pagination">
        <button class="btn-secondary" :disabled="page <= 1" @click="emit('changePage', page - 1)">Zurück</button>
        <span class="pagination-info">Seite {{ page }} von {{ total_pages }}</span>
        <button class="btn-primary" :disabled="page >= total_pages" @click="emit('changePage', page + 1)">Weiter</button>
    </nav>
</template>

<style scoped>
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-l);
}

.pagination-info {
    font-size: var(--text-meta-size);
    line-height: var(--text-meta-line);
    color: var(--color-text-secondary);
}
</style>
