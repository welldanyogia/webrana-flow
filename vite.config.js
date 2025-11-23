import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // 'resources/css/filament/admin/theme.css', // Jika ada tema admin
                'resources/css/filament/dashboard/theme.css', // <--- TAMBAHKAN BARIS INI
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
