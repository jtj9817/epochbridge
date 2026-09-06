import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

export default defineConfig(({ command }) => ({
    // Root-relative url()s in CSS (e.g. /images/sd/...) resolve against the
    // dev-server origin in development, so serve Laravel's public directory
    // there; keep it off for builds to avoid copying public/ into build output.
    publicDir: command === 'serve' ? 'public' : false,
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx', // Inertia/React pages
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        cors: true,
    },
}))