<script setup lang="ts">
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/AuthStore.ts";

defineProps<{
    id: string
    name: string
    model: string
    type: string
    selectable?: boolean
    selectDisabled?: boolean
}>();

const emit = defineEmits(['select']);
const authStore = useAuthStore();
</script>

<template>
    <component
        :is="selectable ? 'div' : RouterLink"
        :to="selectable ? undefined : { name: 'equipment-detail', params: { id } }"
        class="card-link"
    >
        <div class="card card-row">
            <div class="thumb"></div>
            <div>
                <h3>{{ name }}</h3>
                <p class="meta">{{ model }}</p>
                <div class="tags">
                    <span class="tag">{{ type }}</span>
                </div>
            </div>
            <RouterLink v-if="!selectable && authStore.hasRole(['Admin'])" :to="`/inventar/${id}/bearbeiten`" class="btn-secondary edit-button" @click.stop>
                Bearbeiten
            </RouterLink>
            <span class="chevron">›</span>
        </div>

        <button v-if="selectable" class="btn-primary" :disabled="selectDisabled" @click="emit('select', id)">
            {{ selectDisabled ? 'Bereits zugeordnet' : 'Hinzufügen' }}
        </button>
    </component>
</template>

<style scoped>
.card-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

.edit-button {
    flex-shrink: 0;
    min-height: 36px;
    margin-left: auto;
    padding: 0 var(--space-l);
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    white-space: nowrap;
    border-radius: var(--radius-tab);
}

.edit-button + .chevron {
    margin-left: 0;
}
</style>
