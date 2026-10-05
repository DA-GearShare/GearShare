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
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 30px;
  background-color: var(--color-background-areas);
  color: #666;
}

.footer p {
  margin: 0;
}

/* Mobile */
@media (max-width: 600px) {
  .footer {
    flex-direction: column;
    gap: 10px;
  }
}

p {
  color: var(--color-muted);
}
</style>
