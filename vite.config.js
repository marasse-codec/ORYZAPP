import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/charts.js'],
            refresh: true,
        }),
    ],
    /*server: {
        host: '0.0.0.0', // Permet à Vite d'être accessible depuis les autres appareils
        hmr: {
            host: '192.168.1.78', // Remplacez par l'IP exacte de votre PC
        },
    },*/
});
