{{--
    Un tableau de données : défilement horizontal si l'écran est étroit, et, sous 768 px,
    repli en cartes (chaque cellule reprend l'en-tête de sa colonne, cf. resources/js/app.js).
    Écrire les en-têtes dans <thead> et les lignes dans <tbody>.
--}}
<div class="overflow-x-auto">
    <table {{ $attributes->class('tableau-carte min-w-full divide-y divide-[--color-border] text-sm') }}>
        {{ $slot }}
    </table>
</div>
