<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps<{
    title: string;
}>();

const emit = defineEmits<{
    scanned: [code: string];
    close: [];
}>();

const code = ref('');
const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    input.value?.focus();
});

function sendCode() {
    if (code.value.trim() === '') {
        return;
    }

    emit('scanned', code.value.trim());
}
</script>

<template>
    <div class="overlay" @click.self="emit('close')">
        <div class="card modal">
            <h3>{{ title }}</h3>
            <p class="meta">Scanner bereit: Code jetzt scannen (oder eintippen).</p>

            <form class="manual" @submit.prevent="sendCode">
                <input ref="input" v-model="code" type="text" placeholder="Code" autocomplete="off">
                <button type="submit" class="btn-primary" :disabled="!code.trim()">Übernehmen</button>
            </form>

            <button type="button" class="btn-secondary" @click="emit('close')">Abbrechen</button>
        </div>
    </div>
</template>

<style scoped>
.overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-l);
    background: rgba(17, 24, 39, 0.5);
}

.modal {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    width: 100%;
    max-width: 420px;
    padding: var(--space-l);
}

.modal h3,
.modal p {
    margin: 0;
}

.manual {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}
</style>
