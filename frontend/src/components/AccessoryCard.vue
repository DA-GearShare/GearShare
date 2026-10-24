<script setup lang="ts">
    import { useAccessoryStore } from '@/stores/AccessoryStore.ts';
    const props = defineProps<{
        id: string
        name: string
        available: boolean
    }>()

    const accessoryStore = useAccessoryStore()
</script>

<template>
    <label>
        <h3>{{name}}</h3>
        <span v-if="!available">Nicht verfügbar</span>
        <input type="checkbox"
               :checked="accessoryStore.isSelected(props.id)"
               @change="accessoryStore.toggle(id)"
               :disabled="!available"/>
    </label>
</template>

<style scoped>

</style>
