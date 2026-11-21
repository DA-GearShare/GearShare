import './assets/main.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';

import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import { VCalendar } from 'vuetify/components';


const app = createApp(App);

const vuetify = createVuetify({
    components: { VCalendar },
});

app.use(createPinia());
app.use(router);
app.use(vuetify)

app.mount('#app');
