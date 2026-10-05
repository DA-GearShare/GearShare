<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';
import { onMounted } from 'vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';
import { useAuthStore } from '@/stores/AuthStore.ts';

//const authStore = useAuthStore();
//onMounted(() => authStore.fetchUser());
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
