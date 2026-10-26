<script setup lang="ts">
    import { useAccessoryStore } from '@/stores/AccessoryStore.ts';
    import { watch } from 'vue';

    const props = defineProps<{
        id: string
        name: string
        available: boolean
    }>()

    const accessoryStore = useAccessoryStore()

    watch(() => props.available, (available) => {
        if (!available && accessoryStore.isSelected(props.id)){
            accessoryStore.toggle(props.id)
        }
        }, {immediate: true})
</script>

<template>
    <label :class="{ unavailable: !available }">
        <h3>{{name}}</h3>
        <span v-if="!available">Nicht verfügbar</span>
        <input type="checkbox"
               :checked="accessoryStore.isSelected(props.id)"
               @change="accessoryStore.toggle(id)"
               :disabled="!available"/>
    </label>
</template>

<style scoped>
    .unavailable {
        opacity: 0.5;
    }
</style>
