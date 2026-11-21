<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

defineProps<{
    title: string;
}>();

const emit = defineEmits<{
    scanned: [code: string];
    close: [];
}>();

const manualCode = ref('');
const cameraError = ref('');
let scanner: Html5Qrcode | null = null;
let alreadyScanned = false;

function sendCode(code: string) {
    if (alreadyScanned || code.trim() === '') {
        return;
    }

    alreadyScanned = true;
    emit('scanned', code.trim());
}

onMounted(async () => {
    try {
        scanner = new Html5Qrcode('scan-reader');

        await scanner.start(
            { facingMode: 'environment' },
            { fps: 10 },
            (text) => {
                sendCode(text);
            },
            () => {
                // wird aufgerufen, solange kein Code im Bild ist
            },
        );
    } catch (e) {
        console.error(e);
        cameraError.value = 'Die Kamera konnte nicht gestartet werden. Du kannst den Code unten eintippen.';
    }
});

onBeforeUnmount(async () => {
    if (scanner && scanner.isScanning) {
        await scanner.stop();
    }
});
</script>

<template>
    <div class="overlay" @click.self="emit('close')">
        <div class="card modal">
            <h3>{{ title }}</h3>

            <div :id="readerId" class="reader"></div>
            <p v-if="cameraError" class="message-info">{{ cameraError }}</p>

            <form class="manual" @submit.prevent="finish(manualCode)">
                <label>
                    Code manuell eingeben
                    <input v-model="manualCode" type="text" placeholder="z.B. EQ-0004">
                </label>
                <button type="submit" class="btn-secondary" :disabled="!manualCode.trim()">Übernehmen</button>
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
    max-height: 100%;
    padding: var(--space-l);
    overflow-y: auto;
}

.modal h3 {
    margin: 0;
}

.reader {
    width: 100%;
    overflow: hidden;
    border-radius: var(--radius-tab);
}

.manual {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}
</style>
