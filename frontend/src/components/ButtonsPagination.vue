<script setup lang="ts">
    const props = defineProps({
        page: {type: Number, required: true},
        totalPages: {type: Number, required: true},
    });
    const emit = defineEmits(['changePage']);
</script>

<template>
    <nav v-if="totalPages > 1">
        <button v-if="page > 1" @click="emit('changePage', page-1)">Zurück</button>
        <span>Seite {{ page }} von {{ totalPages }}</span>
        <button v-if="page < totalPages" @click="emit('changePage', page+1)">Weiter</button>
    </nav>
</template>
<style scoped>

</style>
