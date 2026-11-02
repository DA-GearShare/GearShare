<script setup lang="ts">
    import { useCartStore } from '@/stores/CartStore.ts';

    const props = defineProps<{
        id: string
        name: string
        available: boolean
        isAdmin: boolean
    }>()

    const emit = defineEmits(['remove'])
    const cart = useCartStore()
</script>

<template>
    <div :class="{ unavailable: !available }">
        <h3>{{name}}</h3>
        <span v-if="!available">Nicht verfügbar</span>
        <button v-if="isAdmin" @click="emit('remove', id)">Entfernen</button>
        <span v-else>
            <button :hidden="!available || cart.has(id)" @click="cart.add(id)">{{ cart.has(id) ? '✓' : '+' }}</button>
            <button :hidden="!available || cart.hasNot(id)" @click="cart.remove(id)">-</button>
        </span>
    </div>
</template>

<style scoped>
    .unavailable {
        opacity: 0.5;
    }
</style>
