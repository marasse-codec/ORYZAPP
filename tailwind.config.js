import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Vert ORYZA #1E5E3A (nuance 700) et Or riz #E5A93B (nuance 400).
const vertOryza = {
    50: '#eef7f1',
    100: '#d5ecdd',
    200: '#aed8bf',
    300: '#7dbd98',
    400: '#4f9d72',
    500: '#2f8055',
    600: '#236c45',
    700: '#1e5e3a',
    800: '#1a4c31',
    900: '#163f29',
    950: '#0b2416',
};

const orRiz = {
    50: '#fdf8ec',
    100: '#faecc9',
    200: '#f4d690',
    300: '#eec05f',
    400: '#e5a93b',
    500: '#d18f22',
    600: '#b4701a',
    700: '#905219',
    800: '#77421b',
    900: '#63381a',
    950: '#391c0a',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
        // 
        './app/Livewire/**/*.php',
        './app/Filament/**/*.php',
        // Ingestion complète des vues Filament (Forms, Tables, Notifications, Support)
    ],

    theme: {
        extend: {
            fontFamily: {
                // Charte ORYZA : Poppins pour toute l'interface.
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Charte ORYZA. « emerald » et « lime » sont redéfinis pour que
                // les vues existantes prennent le vert et l'or de la marque.
                oryza: vertOryza,
                or: orRiz,
                creme: '#F6F3EA',
                noir: '#111111',
                emerald: vertOryza,
                green: vertOryza,
                lime: orRiz,
            },
        },
    },

    plugins: [forms],
};
