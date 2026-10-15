{{-- L'argent de la campagne ($finances, cf. StatistiqueCampagneService::finances). --}}
@php $f = fn ($v) => number_format($v, 0, ',', ' ').' F'; @endphp
<section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    <x-ui.stat libelle="Valeur attribuée" :valeur="$f($finances['engage'])" indice="Toutes attributions, publiées ou non" />
    <x-ui.stat libelle="Montant dû" :valeur="$f($finances['du'])" indice="Attributions publiées" />
    <x-ui.stat libelle="Encaissé" ton="vert" :valeur="$f($finances['encaisse'])"
               :indice="$finances['taux_recouvrement'] !== null ? number_format($finances['taux_recouvrement'], 1, ',', ' ').' % du dû (recouvrement)' : null" />
    <x-ui.stat libelle="Reste à encaisser" :ton="$finances['reste'] > 0 ? 'rouge' : 'vert'" :valeur="$f($finances['reste'])" />
</section>
