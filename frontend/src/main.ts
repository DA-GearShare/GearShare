import './assets/main.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { de } from 'vuetify/locale';

import App from './App.vue';
import router from './router';

import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import { VCalendar } from 'vuetify/components';


const app = createApp(App);

const vuetify = createVuetify({
    components: { VCalendar },
    locale: {
        locale: 'de',
        messages: { de },
    },
    theme: {
        defaultTheme: 'gearshare',
        themes: {
            gearshare: {
                dark: false,
                colors: {
                    primary: '#12273C',
                    secondary: '#2456C8',
                    background: '#F7F8FA',
                    surface: '#FFFFFF',
                    error: '#B91C1C',
                    'on-surface': '#111827',
                },
            },
        },
    },
});

app.use(createPinia());
app.use(router);
app.use(vuetify);

app.mount('#app');
