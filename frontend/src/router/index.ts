import { createRouter, createWebHistory } from 'vue-router';

import InventoryView from '../views/InventoryView.vue';
import InstructionsView from '../views/IntstructionsView.vue';
import LaboratoryView from '../views/LaboratorytimeView.vue';
import LoginViewView from '../views/LoginView.vue';
import AccountViewView from '../views/AccountView.vue';
import { useAuthStore } from '@/stores/AuthStore.ts';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/login'
        },
        {
            path: '/login',
            component: LoginViewView
        },
        {
            path: '/account',
            component: AccountViewView,
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
        }
    ]
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    /*if (to.meta.requiresAuth) {
        await authStore.fetchUser();

        if (!authStore.isLoggedIn) {
            return '/login';
        }

        if (authStore.isLoggedIn) {
            return '/inventar';
        }
    }*/

    return true;
});

export default router;
