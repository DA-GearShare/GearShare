<script setup lang="ts">
import { RouterView } from 'vue-router';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';

</script>

<template>
    <div class="layout">
        <Header />
        <main class="content">
            <RouterView />
        </main>
        <Footer />
    </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.content {
  flex: 1;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}
</style>
