import { sentryVitePlugin } from '@sentry/vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig, loadEnv } from 'vite';
import { ViteImageOptimizer } from 'vite-plugin-image-optimizer';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd())

    return {
        build: {
            cssCodeSplit: true,
            sourcemap: 'hidden',
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (id.includes('node_modules/@tiptap/')) {
                            return 'tiptap';
                        }
                        if (id.includes('node_modules/@sentry/')) {
                            return 'sentry';
                        }
                    },
                },
            },
        },
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.tsx',
                    'resources/css/filament/admin/theme.css',
                    'resources/css/filament/marketplace/theme.css',
                ],
                ssr: 'resources/js/ssr.tsx',
                refresh: true,
            }),
            react(),
            tailwindcss(),
            ViteImageOptimizer(),
            ...(env.VITE_SENTRY_AUTH_TOKEN
                ? [
                      sentryVitePlugin({
                          org: env.VITE_SENTRY_ORG,
                          project: env.VITE_SENTRY_REACT_PROJECT,
                          authToken: env.VITE_SENTRY_AUTH_TOKEN,
                      }),
                  ]
                : []),
        ],
        resolve: {
            alias: {
                'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
            },
        },
        server: {
            cors: true,
            hmr: {
                host: process.env.CODESPACES
                    ? process.env['CODESPACE_NAME'] + '-5173.' + process.env['GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN']
                    : undefined,
                clientPort: process.env.CODESPACES ? 443 : undefined,
                protocol: process.env.CODESPACES ? 'wss' : undefined,
            },
        },
    };
});
