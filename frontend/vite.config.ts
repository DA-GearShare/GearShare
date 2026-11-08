import { fileURLToPath, URL } from 'node:url';

import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    const apiTarget = env.VITE_API_URL || 'http://localhost';
    const publicPort = Number(env.VITE_PUBLIC_PORT || 80);
    const polling = env.CHOKIDAR_USEPOLLING === 'true';

    const allowedHosts = (env.ALLOWED_HOSTS || '').split(',').map((h) => {
        return h.trim();
    }).filter(Boolean);

    return {
        plugins: [vue(), vueDevTools()],
        resolve: {
            alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
        },
        server: {
            host: true,
            port: 5173,
            strictPort: true,
            allowedHosts,
            watch: { usePolling: polling },
            hmr: { clientPort: publicPort },
            proxy: {
                '/api': { target: apiTarget, changeOrigin: true },
            },
        },
    };
});
