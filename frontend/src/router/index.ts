import { createRouter, createWebHistory } from 'vue-router';

import InventoryView from '../views/InventoryView.vue';
import InstructionsView from '../views/IntstructionsView.vue';
import LaboratoryView from '../views/LaboratorytimeView.vue';
import LoginView from '../views/LoginView.vue';
import AccountView from '../views/AccountView.vue';
import AdminView from '../views/AdminView.vue';
import { useAuthStore, type Role } from '@/stores/AuthStore.ts';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/login'
        },
        {
            path: '/login',
            component: LoginView
        },
        {
            path: '/account',
            component: AccountView,
            meta: { requiresAuth: true }
        },
        {
            path: '/inventar',
            component: InventoryView,
            meta: { requiresAuth: true }
        },
        {
            path: '/anleitung',
            component: InstructionsView,
            meta: { requiresAuth: true }
        },
        {
            path: '/laborzeit',
            component: LaboratoryView,
            meta: { requiresAuth: true }
        },
        {
            path: '/admin',
            component: AdminView,
            meta: { requiresAuth: true, roles: ['admin'] }
        }
    ]
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth) {
        await authStore.fetchUser();

        if (!authStore.isLoggedIn) {
            return '/login';
        }

        if (!authStore.hasRole(to.meta.roles as Role[] | undefined)) {
            return '/inventar';
        }
    }

    if (to.path === '/login') {
        await authStore.fetchUser();
        if (authStore.isLoggedIn) return '/inventar';
    }

    return true;
});

export default router;
