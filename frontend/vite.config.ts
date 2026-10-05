import {fileURLToPath, URL} from 'node:url';

import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';

const apiTarget = process.env.VITE_API_TARGET || 'http://localhost'
const publicPort = Number(process.env.VITE_PUBLIC_PORT || 80)
const polling = process.env.CHOKIDAR_USEPOLLING === 'true'

export default defineConfig({
    plugins: [
        vue(),
        vueDevTools(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
        },
    },
    server: {
        host: true,
        port: 5173,
        strictPort: true,
        watch: { usePolling: polling },
        hmr: { clientPort: publicPort },
        proxy: {
            '/api': {
                target: apiTarget,
                changeOrigin: true,
            },
        },
    },

});
