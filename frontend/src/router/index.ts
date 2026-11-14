import { createRouter, createWebHistory } from 'vue-router';

import EquipmentView from '../views/EquipmentView.vue';
import InstructionsView from '../views/InstructionsView.vue';
import LaboratoryView from '../views/LaboratorytimeView.vue';
import LoginView from '../views/LoginView.vue';
import AccountView from '../views/AccountView.vue';
import AdminView from '../views/AdminView.vue';
import AdminUserView from '../views/AdminUserView.vue';
import EquipmentAddView from "@/views/EquipmentAddView.vue";
import EquipmentDetailView from '../views/EquipmentDetailView.vue';
import EquipmentEditView from '../views/EquipmentEditView.vue';
import ReservationView from '../views/ReservationView.vue';
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
            component: EquipmentView,
            meta: { requiresAuth: true }
        },
        {
            path: '/inventar/neu',
            component: EquipmentAddView,
            meta: { requiresAuth: true, roles: ['Admin'] }
        },
        {
            path: '/inventar/:id',
            name: 'equipment-detail',
            component: EquipmentDetailView,
            meta: { requiresAuth: true }
        },
        {
            path: '/inventar/:id/bearbeiten',
            name: 'equipment-edit',
            component: EquipmentEditView,
            meta: { requiresAuth: true, roles: ['Admin'] }
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
            path: '/reservierung',
            component: ReservationView,
            meta: { requiresAuth: true }
        },
        {
            path: '/admin/nutzer',
            component: AdminView,
            meta: { requiresAuth: true, roles: ['Admin'] }
        },
        {
            path: '/admin/nutzer/:id',
            component: AdminUserView,
            meta: { requiresAuth: true, roles: ['Admin'] }
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
