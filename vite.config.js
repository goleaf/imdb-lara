import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        cssMinify: 'lightningcss',
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('/vendor/livewire/livewire/dist/livewire.esm.js')) {
                        return 'livewire';
                    }

                    if (id.includes('/node_modules/@alpinejs/') || id.includes('/node_modules/@sheaf/rover/')) {
                        return 'ui-interactions';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
