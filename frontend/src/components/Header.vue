<script setup lang="ts">
import { User, ShoppingCart } from 'lucide-vue-next';
</script>

<template>
    <header class="header">

        <div class="top">
            <RouterLink to="/inventar">
                <img src="@/assets/logo.svg" alt="Logo" class="logo" />
            </RouterLink>

            <div class="actions">
                <button>
                    <ShoppingCart />
                </button>

                <RouterLink to="/account" aria-label="Mein Konto">
                    <User />
                </RouterLink>
            </div>
        </div>

        <nav>
            <RouterLink to="/inventar">Inventar</RouterLink>
            <RouterLink to="/anleitung">Anleitung</RouterLink>
            <RouterLink to="/laborzeit">Laborzeit</RouterLink>
        </nav>

    </header>
</template>

<style scoped>
.header {
  padding: 16px 24px;
  background-color: var(--color-background-areas);
}

.top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  height: 40px;
  max-width: 180px;
}

.actions {
  display: flex;
  gap: 16px;
}

.actions button,
.actions a {
  display: flex;
  align-items: center;
  border: none;
  background: none;
  padding: 5px;
  cursor: pointer;
  color: inherit;
  text-decoration: none;
}

.actions svg {
  width: 26px;
  height: 26px;
}

nav {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 3rem;
  max-width: 40rem;
  margin: 1rem auto 0;
}

nav a {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 13px;
  font-size: 16px;
  font-weight: 600;
  color: inherit;
  text-decoration: none;
  white-space: nowrap;
  border-radius: 14px;
}

nav a:hover {
  background-color: var(--color-soft-surface);
}

nav a.router-link-active {
  background-color: var(--color-navy);
  color: white;
}

@media (max-width: 800px) {
  .header {
    padding: 12px 16px;
  }

  .logo {
    height: 32px;
    max-width: 140px;
  }

  .actions {
    gap: 8px;
  }

  .actions svg {
    width: 22px;
    height: 22px;
  }

  nav a {
    font-size: 14px;
    padding: 8px 10px;
  }
}


</style>
