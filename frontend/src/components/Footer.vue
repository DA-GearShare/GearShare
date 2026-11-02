<script setup lang="ts">

</script>

<template>
    <footer class="footer">
        <p>GearShare - HTL Rennweg</p>
        <p>Kontakt: 2033@htl.rennweg.at</p>
        <a href="/datenschutz">Datenschutzerklärung</a>
        <a href="/impressum">Impressum</a>
    </footer>
</template>

<style scoped>
.footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: var(--space-xs) var(--space-xl);
    padding: var(--space-xl) var(--space-l);
    background: var(--color-background-areas);
    border-top: 1px solid var(--color-border);
}

.footer p {
    margin: 0;
}

.footer p,
.footer a {
    font-size: var(--text-meta-size);
    line-height: var(--text-meta-line);
    color: var(--color-text-secondary);
}
</style>
