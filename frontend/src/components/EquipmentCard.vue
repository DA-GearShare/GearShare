<script setup lang="ts">
    import {RouterLink} from "vue-router";

    defineProps<{
        id: string
        name: string
        model: string
        type: string
        status: string
        selectable?: boolean
        selectDisabled?: boolean
    }>();

    const emit = defineEmits(['select'])
</script>

<template>
    <component
        :is="selectable ? 'div' : RouterLink"
        :to="selectable ? undefined: {name: 'equipment-detail', params: {id}}"
    >
        <div class="thumb"></div>
        <div class="info">
            <h3 class="title">{{ name }}</h3>
            <p class="subtitle">{{ model }}</p>
            <div class="tags">
                <span class="tag">{{ type }}</span>
                <span class="tag">{{ status }}</span>
            </div>
        </div>

        <button v-if="selectable" :disabled="selectDisabled" @click="emit('select', id)">
            {{ selectDisabled ? 'Bereits zugeordnet' : 'Hinzufügen' }}
        </button>
    </component>
</template>
