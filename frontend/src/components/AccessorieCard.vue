<script setup lang="ts">
    defineProps<{
        name: string
        available: boolean
    }>()

    const checked = defineModel<boolean>({default: false})
</script>

<template>
    <label>
        <h3>{{name}}</h3>
        <span v-if="!available">Nicht verfügbar</span>
        <input type="checkbox" v-model="checked" :disabled="!available" />
    </label>
</template>

<style scoped>

</style>
