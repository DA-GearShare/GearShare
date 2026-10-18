<script setup lang="ts">
const emit = defineEmits(['search']);
const search = (e) => {
    emit('search', e.target.value);
};
</script>

<template>
    <form>
        <div>
            <input type="text" placeholder="Suche" @input="search">
        </div>
    </form>
</template>
<style scoped>

</style>
