{{-- Commission : ce qui attend son arbitrage ou sa publication --}}
<section class="space-y-3">
    <h2 class="text-base font-semibold text-[--color-ink]">Commission</h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <x-ui.stat libelle="Séances ouvertes" :valeur="$commission['sessions_ouvertes']" :href="route('commissions.index')" />
        <x-ui.stat libelle="Demandes à arbitrer" :valeur="$commission['demandes_a_arbitrer']" :href="route('demandes.index')" ton="ambre" />
        <x-ui.stat libelle="Attributions à publier" :valeur="$commission['attributions_a_publier']" :href="route('commissions.index')" ton="ambre" />
    </div>
</section>
