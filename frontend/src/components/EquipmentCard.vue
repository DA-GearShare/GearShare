
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

            <div class="card-info">
                <h3>{{ name }}</h3>
                <p class="meta">{{ model }}</p>
                <div class="tags">
                    <span class="tag">{{ type }}</span>
                </div>
            </div>

            <RouterLink
                v-if="!selectable && authStore.hasRole(['Admin'])"
                :to="`/inventar/${id}/bearbeiten`"
                class="btn-secondary edit-button"
                @click.stop
            >
                Bearbeiten
            </RouterLink>

            <span class="chevron">›</span>
        </div>

        <button
            v-if="selectable"
            class="btn-primary"
            :disabled="selectDisabled"
            @click="emit('select', id)"
        >
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

.card-row {
    display: flex;
    align-items: center;
    gap: var(--space-l);
    min-width: 0;
}

.card-info {
    flex: 1;
    min-width: 0;
}

.card-info h3 {
    overflow-wrap: anywhere;
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

/* Tablet und Mobile */
@media (max-width: 600px) {
    .card-row {
        gap: var(--space-s);
    }

    .thumb {
        width: 64px;
        height: 64px;
        flex-shrink: 0;
    }

    .edit-button {
        padding: 0 var(--space-s);
        font-size: 11px;
    }

    .chevron {
        font-size: 22px;
    }
}
</style>
