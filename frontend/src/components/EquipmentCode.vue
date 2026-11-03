<script setup lang="ts">
import { ref, watch } from 'vue';
import QRCode from 'qrcode';
import { jsPDF } from 'jspdf';

const props = defineProps<{
    code: string
    name?: string
}>();

const qrImage = ref('');

watch(() => {
    return props.code;
}, async (value) => {
    qrImage.value = value ? await QRCode.toDataURL(value, { width: 512, margin: 2 }) : '';
}, { immediate: true });

function downloadPng() {
    const link = document.createElement('a');
    link.href = qrImage.value;
    link.download = `${props.code}.png`;
    link.click();
}

function downloadPdf() {
    const pdf = new jsPDF({ unit: 'mm', format: 'a6' });
    const pageWidth = pdf.internal.pageSize.getWidth();

    if (props.name) {
        pdf.setFontSize(14);
        pdf.text(props.name, pageWidth / 2, 15, { align: 'center' });
    }

    pdf.addImage(qrImage.value, 'PNG', (pageWidth - 70) / 2, 25, 70, 70);

    pdf.setFontSize(16);
    pdf.text(props.code, pageWidth / 2, 108, { align: 'center' });

    pdf.save(`${props.code}.pdf`);
}
</script>

<template>
    <div class="qr-code">
        <img v-if="qrImage" :src="qrImage" :alt="`QR-Code ${code}`" class="qr-image">

        <div class="toolbar">
            <button type="button" class="btn-secondary" @click="downloadPng">Code als PNG</button>
            <button type="button" class="btn-secondary" @click="downloadPdf">Code als PDF</button>
        </div>
    </div>
</template>

<style scoped>
.qr-code {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-l);
}

.qr-image {
    width: 200px;
    height: 200px;
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-tab);
}
</style>
