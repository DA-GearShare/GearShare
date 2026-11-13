
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { User, ShoppingCart, ClipboardList, BriefcaseBusiness, Users, LogOut } from 'lucide-vue-next';
import { useAuthStore } from "@/stores/AuthStore.ts";

const authStore = useAuthStore();
const router = useRouter();

const isProfileOpen = ref(false);
const profileMenu = ref<HTMLElement | null>(null);

function goToReservations() {
    router.push('/reservierung');
}

const toggleProfile = () => {
    isProfileOpen.value = !isProfileOpen.value;
};

const closeProfile = () => {
    isProfileOpen.value = false;
};

const handleOutsideClick = (event: MouseEvent) => {
    if (
        profileMenu.value &&
        !profileMenu.value.contains(event.target as Node)
    ) {
        closeProfile();
    }
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeProfile();
    }
};

const handleLogout = async () => {
    closeProfile();
    await authStore.logout();
    router.push('/login');
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
    document.removeEventListener('keydown', handleEscape);
});

</script>

<template>
    <header class="header">
        <RouterLink to="/inventar" class="logo-link" aria-label="Zum Inventar">
            <img src="@/assets/logo.svg" alt="GearShare" class="logo" />
        </RouterLink>

        <nav class="nav">
            <RouterLink to="/inventar" class="nav-link" v-if="authStore.isLoggedIn">
                Inventar
            </RouterLink>

            <RouterLink to="/laborzeit" class="nav-link" v-if="authStore.isLoggedIn">
                Laborzeit
            </RouterLink>

            <RouterLink to="/anleitung" class="nav-link">
                Anleitung
            </RouterLink>
        </nav>

        <div class="actions" v-if="authStore.isLoggedIn">
            <button type="button" class="icon-button" aria-label="Reservierungen" @click="goToReservations">
                <ShoppingCart />
            </button>

            <div class="profile-container" ref="profileMenu" v-if="authStore.isLoggedIn">
                <button type="button" class="icon-button" aria-label="Mein Profil" :aria-expanded="isProfileOpen" aria-controls="profile-dropdown" @click="toggleProfile">
                    <User />
                </button>

                <div v-if="isProfileOpen" id="profile-dropdown" class="profile-dropdown">
                    <div class="dropdown-items">

                        <RouterLink to="/account" class="dropdown-item" @click="closeProfile">
                            <ClipboardList />
                            <span>Meine Reservierungen</span>
                        </RouterLink>

                        <RouterLink v-if="authStore.hasRole(['Admin'])" to="/admin/reservierungen" class="dropdown-item" @click="closeProfile">
                            <BriefcaseBusiness />
                            <span>Reservierübersicht (Admin)</span>
                        </RouterLink>

                        <RouterLink v-if="authStore.hasRole(['Admin'])" to="/admin/nutzer" class="dropdown-item" @click="closeProfile">
                            <Users />
                            <span>Nutzerverwaltung (Admin)</span>
                        </RouterLink>

                    </div>

                    <div class="dropdown-divider"></div>

                    <button type="button" class="dropdown-item logout-item" @click="handleLogout">
                        <LogOut />
                        <span>Abmelden</span>
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    padding: var(--space-m) var(--space-xl);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
}

.logo-link {
    justify-self: start;
}

.logo {
    display: block;
    height: 40px;
    max-width: 180px;
}

.nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-xl);
}

.nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 var(--space-l);
    border-radius: var(--radius-tab);
    white-space: nowrap;
}

.nav-link:hover {
    background: var(--color-border);
}

.nav-link.router-link-active {
    color: var(--color-surface);
    background: var(--color-navy);
}

.actions {
    display: flex;
    justify-self: end;
    gap: var(--space-s);
}

.icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    padding: 0;
    color: var(--color-text-primary);
    background: none;
    border: none;
    border-radius: var(--radius-tab);
    cursor: pointer;
}

.icon-button:hover {
    background: var(--color-border);
}

.icon-button svg {
    width: 24px;
    height: 24px;
}

.profile-container {
    position: relative;
}

.profile-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 1000;
    width: 250px;
    min-width: unset;
    padding: 10px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(17, 24, 39, 0.12);
}

.dropdown-items {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    min-height: 46px;
    padding: 8px 10px;
    color: var(--color-text-primary);
    font: inherit;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    text-decoration: none;
    background: transparent;
    border: none;
    border-radius: var(--radius-tab);
    cursor: pointer;
    white-space: nowrap;
}

.dropdown-item svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    color: var(--color-navy);
}

.dropdown-item:hover {
    background: var(--color-soft-surface);
}

.dropdown-divider {
    height: 1px;
    margin: var(--space-l) 0;
    background: var(--color-border);
}

.logout-item {
    color: #dc2626;
}

.logout-item svg {
    color: #dc2626;
}

@media (max-width: 800px) {
    .header {
        grid-template-columns: 1fr auto;
        padding: var(--space-m) var(--space-l);
    }

    .logo {
        height: 32px;
    }

    .nav {
        grid-column: 1 / -1;
        grid-row: 2;
        flex-wrap: wrap;
        gap: 65px;
        margin-top: var(--space-m);
    }

    .nav-link {
        padding: 0 var(--space-m);
    }
}

@media (max-width: 480px) {
    .profile-dropdown {
        min-width: 0;
        width: min(380px, calc(100vw - 24px));
        padding: var(--space-m);
    }

    .dropdown-item {
        gap: var(--space-m);
        min-height: 58px;
        padding: var(--space-s);
        font-size: 15px;
    }

    .dropdown-item svg {
        width: 24px;
        height: 24px;
    }
}
</style>
