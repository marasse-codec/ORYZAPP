@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-orange-200 px-6 py-8">
    <div class="plein-ecran max-w-6xl mx-auto space-y-6"
         x-data="{
             postes: {{ Illuminate\Support\Js::from($postes) }},
             seanceOuverte: {{ $seance ? 'true' : 'false' }},
             recherche: '',
             sauvegarde: 'repos',
             derniereMaj: null,
             pleinEcran: false,

             /* Le restant se recalcule à la frappe : c'est tout l'intérêt de
                l'écran. Le serveur, lui, n'est sollicité que pour retenir le
                brouillon. */
             restant(poste) { return poste.disponible - this.quantite(poste); },
             quantite(poste) { return Number(poste.simulee) || 0; },

             get postesFiltres() {
                 const terme = this.recherche.trim().toLowerCase();
                 return terme === ''
                     ? this.postes
                     : this.postes.filter(poste => poste.libelle.toLowerCase().includes(terme));
             },

             get totalSimule() {
                 return this.postes.reduce((somme, poste) => somme + this.quantite(poste), 0);
             },

             get postesEnTension() {
                 return this.postes.filter(poste => this.restant(poste) < 0).length;
             },

             async noter(poste) {
                 this.sauvegarde = 'encours';

                 try {
                     const reponse = await fetch('{{ route('attributions.simulation.noter') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         },
                         body: JSON.stringify({ id_appui: poste.id_appui, quantite: this.quantite(poste) }),
                     });

                     this.sauvegarde = reponse.ok ? 'enregistre' : 'erreur';
                 } catch (e) {
                     this.sauvegarde = 'erreur';
                 }
             },

             /* Le brouillon est commun : sans relecture, chacun arbitrerait sur
                les chiffres figés au chargement de sa propre page. */
             async rafraichir() {
                 let donnees;

                 try {
                     const reponse = await fetch('{{ route('attributions.simulation.etat') }}', {
                         headers: { 'Accept': 'application/json' },
                     });

                     if (! reponse.ok) return;
                     donnees = await reponse.json();
                 } catch (e) {
                     return;
                 }

                 /* Séance clôturée pendant la consultation : le serveur rendra
                    l'écran figé, avec sa bannière. */
                 if (this.seanceOuverte && ! donnees.seance_ouverte) {
                     window.location.reload();
                     return;
                 }

                 /* Le champ en cours de frappe ne se fait pas écraser sous les
                    doigts de celui qui le remplit. */
                 const enSaisie = document.activeElement?.id ?? '';

                 donnees.postes.forEach(frais => {
                     const local = this.postes.find(poste => poste.id_appui === frais.id_appui);
                     if (! local) return;

                     Object.assign(local, frais, enSaisie === `simulee-${frais.id_appui}`
                         ? { simulee: local.simulee }
                         : {});
                 });

                 this.derniereMaj = new Date().toLocaleTimeString('fr-FR');
             },

             /* Projection en salle : c'est le tableau seul qu'on met en plein
                écran, la barre latérale et l'en-tête sortent donc du cadre.

                $root, pas $el : appelée depuis le @click du bouton, la méthode
                verrait dans $el le bouton lui-même — c'est lui qu'on afficherait
                en grand. */
             basculerPleinEcran() {
                 if (document.fullscreenElement) {
                     document.exitFullscreen();
                 } else {
                     this.$root.requestFullscreen().catch(() => {});
                 }
             },
         }"
         x-init="setInterval(() => rafraichir(), 10000)"
         @fullscreenchange.document="pleinEcran = document.fullscreenElement === $root">

        {{-- En-tête --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">
                        Simulation des sorties de stock sur attribution
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Posez vos hypothèses d'arbitrage et voyez le disponible fondre en direct.
                        Rien n'est enregistré en base : ni attribution, ni sortie de stock.
                        Le tableau est commun à toute la séance : chaque membre y écrit et le relit.
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        « Demandé » ne retient que les dossiers instruits et validés pour la commission,
                        c'est-à-dire ceux dont tous les services concernés ont rendu leur accord.
                        Ce qui est déjà attribué se lit dans « Déjà engagé ».
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    @if ($seance)
                        <span class="rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-orange-800">
                            Séance d'arbitrage active
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Aucune séance active
                        </span>
                    @endif

                    <button type="button" @click="basculerPleinEcran()"
                            :title="pleinEcran ? 'Quitter le plein écran (Échap)' : 'Projeter le tableau en plein écran'"
                            class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined text-base"
                              x-text="pleinEcran ? 'close_fullscreen' : 'open_in_full'">open_in_full</span>
                        <span x-text="pleinEcran ? 'Réduire' : 'Plein écran'">Plein écran</span>
                    </button>
                </div>
            </div>
        </div>

        @include('partials.flash')

        @unless ($seance)
            <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                Aucune séance de commission n'est ouverte : les disponibilités restent consultables,
                mais la simulation est figée.
                <a href="{{ route('commissions.create') }}" class="font-semibold underline">Ouvrir une séance</a>
            </div>
        @endunless

        {{-- Recherche : filtrage immédiat, le catalogue est déjà en page. --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <label for="recherche" class="block text-sm font-medium text-slate-700">Rechercher un appui</label>
            <input id="recherche" type="search" x-model="recherche"
                   placeholder="Ex. : NPK, urée…"
                   class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[--color-primary] focus:ring-[--color-primary]">
        </div>

        {{-- Les postes --}}
        @if ($postes->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                Aucun appui stockable au catalogue : il n'y a rien à simuler.
                <a href="{{ route('appuis.create') }}" class="font-semibold text-[--color-primary] hover:underline">Créer un appui</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <template x-for="poste in postesFiltres" :key="poste.id_appui">
                    {{-- Hauteur fixe : sans elle, les cartes se décalent au fil de
                         la saisie — le message de dépassement qui apparaît suffit à
                         faire sauter toute la ligne de la grille sous les yeux de la
                         commission. Chaque bloc interne a donc son emplacement
                         réservé, occupé ou non. --}}
                    <div class="flex h-72 flex-col rounded-xl border bg-white p-5 shadow-sm"
                         :class="restant(poste) < 0 ? 'border-red-300' : 'border-slate-200'">

                        {{-- Deux lignes au plus : le libellé complet reste en infobulle. --}}
                        <h2 class="line-clamp-2 h-14 text-lg font-bold leading-7 text-slate-800"
                            :title="poste.libelle" x-text="poste.libelle"></h2>
                        <p class="h-4 truncate text-xs text-slate-400"
                           :title="poste.service" x-text="poste.service"></p>

                        <dl class="mt-3 space-y-1 text-sm text-slate-600">
                            <div class="flex justify-between">
                                <dt>Demandé</dt>
                                <dd class="font-medium" x-text="`${poste.demandee} ${poste.unite}`"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Déjà engagé</dt>
                                <dd class="font-medium" x-text="`${poste.engagee} ${poste.unite}`"></dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-1">
                                <dt class="font-semibold">Restant</dt>
                                <dd class="font-bold"
                                    :class="restant(poste) < 0 ? 'text-red-700' : (restant(poste) === 0 ? 'text-orange-700' : 'text-emerald-700')"
                                    x-text="`${restant(poste)} ${poste.unite}`"></dd>
                            </div>
                        </dl>

                        {{-- mt-auto : la ligne de saisie tombe au même endroit sur
                             toutes les cartes, quel que soit ce qui la précède. --}}
                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-200 pt-3">
                            <label class="text-sm font-medium text-slate-700"
                                   :for="`simulee-${poste.id_appui}`">Attribué</label>
                            <input type="number" min="0" step="1"
                                   :id="`simulee-${poste.id_appui}`"
                                   x-model="poste.simulee"
                                   @input.debounce.400ms="noter(poste)"
                                   @focus="$event.target.select()"
                                   {{ $seance ? '' : 'disabled' }}
                                   class="w-24 rounded-md border-2 border-[--color-secondary] text-right text-sm font-semibold text-[--color-secondary] focus:border-[--color-secondary] focus:ring-[--color-secondary] disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-400">
                        </div>

                        {{-- Emplacement toujours occupé : masquer ce message le ferait
                             disparaître du flux et la carte changerait de taille. --}}
                        <p class="mt-1.5 h-4 text-xs font-medium text-red-700"
                           x-text="restant(poste) < 0
                               ? `Dépassement de ${Math.abs(restant(poste))} ${poste.unite}.`
                               : ''"></p>
                    </div>
                </template>
            </div>

            <p class="text-center text-sm text-slate-500" x-show="postesFiltres.length === 0" x-cloak>
                Aucun appui ne correspond à « <span x-text="recherche"></span> ».
            </p>
        @endif

        {{-- Récapitulatif de séance --}}
        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-600">
                <p>
                    <span class="font-semibold text-[--color-primary]" x-text="totalSimule"></span>
                    unité(s) simulée(s) sur
                    <span class="font-semibold" x-text="postes.length"></span> appui(s).
                </p>
                <p class="text-xs" :class="postesEnTension > 0 ? 'font-semibold text-red-700' : 'text-slate-500'"
                   x-text="postesEnTension > 0
                       ? `${postesEnTension} appui(s) en dépassement du disponible.`
                       : 'Aucun dépassement du disponible.'"></p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400" x-show="sauvegarde === 'encours'" x-cloak>Enregistrement…</span>
                <span class="text-xs text-emerald-700" x-show="sauvegarde === 'enregistre'" x-cloak>Brouillon enregistré</span>
                <span class="text-xs font-semibold text-red-700" x-show="sauvegarde === 'erreur'" x-cloak>
                    Brouillon non enregistré — rechargez la page.
                </span>
                <span class="text-xs text-slate-400" x-show="derniereMaj && sauvegarde === 'repos'" x-cloak
                      x-text="`Relu à ${derniereMaj}`"></span>

                <form action="{{ route('attributions.simulation.reinitialiser') }}" method="POST"
                      onsubmit="return confirm('Vider la simulation pour tous les membres de la séance ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Réinitialiser
                    </button>
                </form>
            </div>
        </div>

        <p class="text-xs text-slate-500">
            La simulation n'engage rien. Les attributions définitives se saisissent dossier par dossier
            depuis <a href="{{ route('attributions.index') }}" class="font-medium text-[--color-primary] hover:underline">les attributions</a>.
        </p>
    </div>
</div>
@endsection
