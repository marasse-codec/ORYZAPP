@extends($layout ?? 'layouts.aderiz')

@php
    // État initial de l'écran : le catalogue, et la saisie précédente si le serveur a refusé le lot.
    $etat = [
        'catalogue' => $catalogue,
        'max' => $maxActeurs,
        'appuis' => array_values(old('appuis', [])),
        'acteurs' => array_values(old('acteurs', [])),
        'soumettre' => (bool) old('soumettre'),
    ];
@endphp

@section('content')
<script>
    /* La grille de saisie. Une ligne = un acteur = une demande. Les appuis sont communs à tout le lot :
       chaque acteur n'y ajoute que sa quantité. */
    window.serieDemandes = function (init) {
        const vide = () => ({ nom: '', date_naissance: '', telephone: '', nom_structure: '', visite: '', quantites: {} });

        return {
            catalogue: init.catalogue,
            max: init.max,
            prochaineCle: 0,
            appuis: [],
            lignes: [],
            envoi: false,
            // Mise en place : la fin doit être strictement postérieure au début.
            debut: '',
            fin: '',
            get finMin() {
                if (!this.debut) return null;
                const d = new Date(this.debut + 'T00:00:00');
                d.setDate(d.getDate() + 1);
                return d.toLocaleDateString('sv-SE');
            },
            collerOuvert: false,
            texteColle: '',
            messageImport: '',

            init() {
                this.soumettre = Boolean(init.soumettre);
                // Au retour d'une erreur serveur, on reprend la saisie telle qu'elle était.
                this.appuis = (init.appuis.length ? init.appuis : [{ id_type_appui: '', id_appui: '' }])
                    .map(a => ({ cle: this.prochaineCle++, id_type_appui: a.id_type_appui ?? '', id_appui: a.id_appui ?? '' }));
                this.lignes = init.acteurs.length
                    ? init.acteurs.map(l => ({ ...vide(), ...l, quantites: this.quantitesInitiales(l.quantites || {}) }))
                    : Array.from({ length: 5 }, vide);
            },

            quantitesInitiales(parPosition) {
                const parCle = {};
                this.appuis.forEach((a, k) => { parCle[a.cle] = parPosition[k] ?? ''; });
                return parCle;
            },

            /* ---- Appuis communs ---- */
            ajouterAppui() { this.appuis.push({ cle: this.prochaineCle++, id_type_appui: '', id_appui: '' }); },
            supprimerAppui(k) { if (this.appuis.length > 1) this.appuis.splice(k, 1); },
            // Une demande ne porte qu'une ligne par type d'appui : un type déjà pris disparaît des autres listes.
            typesDisponibles(k) {
                const pris = this.appuis.filter((a, i) => i !== k).map(a => String(a.id_type_appui));
                return this.catalogue.filter(t => !pris.includes(String(t.id)));
            },
            appuisDuType(idType) { return (this.catalogue.find(t => String(t.id) === String(idType)) || { appuis: [] }).appuis; },
            uniteDe(appui) {
                const trouve = this.appuisDuType(appui.id_type_appui).find(x => String(x.id) === String(appui.id_appui));
                return trouve ? trouve.unite : '';
            },
            libelleAppui(appui) {
                const type = this.catalogue.find(t => String(t.id) === String(appui.id_type_appui));
                return type ? type.libelle : 'Appui';
            },
            typeChange(appui) {
                const choix = this.appuisDuType(appui.id_type_appui);
                appui.id_appui = choix.length === 1 ? choix[0].id : '';
            },

            /* ---- Grille ---- */
            ajouterLigne() { this.lignes.push(vide()); },
            supprimerLigne(i) { if (this.lignes.length > 1) this.lignes.splice(i, 1); },
            estVide(l) {
                return !l.nom && !l.date_naissance && !l.telephone && !l.nom_structure && !l.visite
                    && Object.values(l.quantites).every(q => q === '' || q === null);
            },
            get nbActeurs() { return this.lignes.filter(l => !this.estVide(l)).length; },
            get depasse() { return this.nbActeurs > this.max; },
            /* Pas de soumission sans visite de terrain : les lignes saisies sans observations de visite. */
            get sansVisite() { return this.lignes.filter(l => !this.estVide(l) && !String(l.visite).trim()).length; },
            soumettre: false,

            /* Entrée = cellule du dessous, comme dans un tableur ; sur la dernière ligne, elle en ouvre une. */
            suivante(i, colonne) {
                if (i === this.lignes.length - 1) this.ajouterLigne();
                this.$nextTick(() => document.querySelector(`[data-cellule="${i + 1}-${colonne}"]`)?.focus());
            },

            /* Collage depuis Excel : nom ⇥ date ⇥ téléphone ⇥ structure ⇥ une quantité par appui, dans l'ordre, puis la visite. */
            importer() {
                const date = (v) => {
                    const m = String(v || '').trim().match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/);
                    return m ? `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}` : String(v || '').trim();
                };
                const lues = this.texteColle.split(/\r?\n/)
                    .map(ligne => ligne.split(/\t|;/).map(c => c.trim()))
                    .filter(c => c[0])
                    .map(c => {
                        const l = vide();
                        l.nom = c[0]; l.date_naissance = date(c[1]); l.telephone = c[2] || ''; l.nom_structure = c[3] || '';
                        this.appuis.forEach((a, k) => { l.quantites[a.cle] = c[4 + k] ?? ''; });
                        l.visite = c[4 + this.appuis.length] ?? '';
                        return l;
                    });

                if (lues.length === 0) { this.messageImport = 'Aucune ligne reconnue.'; return; }

                this.lignes = this.lignes.filter(l => !this.estVide(l)).concat(lues);
                this.messageImport = `${lues.length} ligne(s) ajoutée(s).`;
                this.texteColle = '';
                this.collerOuvert = false;
            },

            /* Les lignes vides sont écartées avant l'envoi : les numéros de ligne des messages restent ceux de l'écran. */
            async envoyer(formulaire) {
                if (this.nbActeurs === 0 || this.depasse) return;
                this.envoi = true;
                this.lignes = this.lignes.filter(l => !this.estVide(l));
                await this.$nextTick();
                formulaire.submit();
            },
        };
    };
</script>

<div>
    <div class="max-w-6xl mx-auto space-y-6" x-data="serieDemandes({{ Illuminate\Support\Js::from($etat) }})"
         x-init="debut = {{ Illuminate\Support\Js::from(old('periode_debut_mise_en_place', '')) }}; fin = {{ Illuminate\Support\Js::from(old('periode_fin_mise_en_place', '')) }}"
         x-effect="if (fin && finMin && fin < finMin) fin = ''">

        <div>
            <h1 class="text-2xl font-bold text-slate-800">Saisie en série</h1>
            <p class="text-sm text-slate-600">
                Enregistrez d'un coup les demandes de plusieurs acteurs qui demandent la même chose :
                définissez ce qui est commun au lot, puis saisissez une ligne par acteur.
            </p>
        </div>


        {{-- Les erreurs elles-mêmes s'affichent en tête de page (coque) ; ici, ce qu'elles impliquent pour le lot. --}}
        @if ($errors->any())
            <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Le lot n'a pas été enregistré : <strong>rien n'a été créé</strong>. Corrigez les points signalés en haut de page, puis relancez.
            </p>
        @endif

        <form method="POST" action="{{ route('demandes.serie.store') }}" class="space-y-6" @submit.prevent="envoyer($el)">
            @csrf

            {{-- 1. Ce qui est commun au lot --}}
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">1. Ce qui est commun au lot</h2>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="id_campagne" class="block text-sm font-medium text-slate-700">Campagne</label>
                        <select id="id_campagne" name="id_campagne" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($campagnes as $campagne)
                                <option value="{{ $campagne->id_campagne }}" @selected((int) old('id_campagne', $campagneParDefaut) === $campagne->id_campagne)>
                                    {{ $campagne->lib_campagne }} ({{ $campagne->mois_debut_campagne->format('m/Y') }} - {{ $campagne->mois_fin_campagne->format('m/Y') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="id_type_acteur" class="block text-sm font-medium text-slate-700">Type d'acteur</label>
                        <select id="id_type_acteur" name="id_type_acteur" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Choisir</option>
                            @foreach ($typesActeurs as $type)
                                <option value="{{ $type->id_type_acteur }}" @selected((int) old('id_type_acteur') === $type->id_type_acteur)>{{ $type->lib_type_acteur }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:row-span-2">
                        <label for="localites" class="block text-sm font-medium text-slate-700">Localité(s)</label>
                        <select id="localites" name="localites[]" multiple size="6" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm" style="background-image: none;">
                            @foreach ($localites->groupBy(fn ($l) => $l->departement?->lib_departement ?? 'Autres') as $departement => $groupe)
                                <optgroup label="{{ $departement }}">
                                    @foreach ($groupe as $localite)
                                        <option value="{{ $localite->id_localite }}" @selected(in_array($localite->id_localite, array_map('intval', old('localites', []))))>{{ $localite->lib_localite }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Ctrl (ou ⌘) + clic pour en choisir plusieurs.</p>
                    </div>

                    <div>
                        <label for="periode_debut_mise_en_place" class="block text-sm font-medium text-slate-700">Mise en place : début</label>
                        <input id="periode_debut_mise_en_place" name="periode_debut_mise_en_place" type="date" required x-model="debut" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="periode_fin_mise_en_place" class="block text-sm font-medium text-slate-700">Mise en place : fin</label>
                        <input id="periode_fin_mise_en_place" name="periode_fin_mise_en_place" type="date" required x-model="fin" :min="finMin" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </div>
                </div>
            </section>

            {{-- 2. Appuis demandés par tout le lot --}}
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-800">2. Appuis demandés</h2>
                    <button type="button" @click="ajouterAppui()" class="text-sm font-medium text-[--color-primary] hover:underline">+ Ajouter un appui</button>
                </div>

                <div class="space-y-3">
                    <template x-for="(appui, k) in appuis" :key="appui.cle">
                        <div class="grid items-end gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="'Type d\'appui n°' + (k + 1)"></label>
                                <select :name="`appuis[${k}][id_type_appui]`" x-model="appui.id_type_appui" @change="typeChange(appui)"
                                        required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">Choisir</option>
                                    <template x-for="t in typesDisponibles(k)" :key="t.id">
                                        <option :value="t.id" x-text="t.libelle" :selected="String(t.id) === String(appui.id_type_appui)"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="md:col-span-5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Appui précis (facultatif)</label>
                                <select :name="`appuis[${k}][id_appui]`" x-model="appui.id_appui" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">Non précisé</option>
                                    <template x-for="a in appuisDuType(appui.id_type_appui)" :key="a.id">
                                        <option :value="a.id" x-text="a.libelle + (a.unite ? ' (' + a.unite + ')' : '')" :selected="String(a.id) === String(appui.id_appui)"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="md:col-span-2 text-right">
                                <button type="button" @click="supprimerAppui(k)" x-show="appuis.length > 1" class="text-sm text-red-600 hover:underline">Retirer</button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            {{-- 3. Les acteurs --}}
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-slate-800">
                        3. Acteurs
                        <span class="ml-1 text-sm font-normal text-slate-500" x-text="`(${nbActeurs} saisi(s))`"></span>
                    </h2>
                    <div class="flex items-center gap-4 text-sm">
                        <button type="button" @click="collerOuvert = !collerOuvert" class="font-medium text-[--color-primary] hover:underline">Coller depuis un tableur</button>
                        <button type="button" @click="ajouterLigne()" class="font-medium text-[--color-primary] hover:underline">+ Ajouter une ligne</button>
                    </div>
                </div>

                <p class="mb-3 text-xs text-slate-500">
                    Astuce : <kbd class="rounded border px-1">Entrée</kbd> passe à la ligne suivante, comme dans un tableur.
                    Les pièces justificatives se joignent ensuite, demande par demande.
                    Les observations de visite de terrain sont facultatives à l'enregistrement, mais indispensables pour soumettre.
                </p>

                <div x-show="collerOuvert" x-cloak class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <label class="block text-sm font-medium text-slate-700" for="texteColle">
                        Collez des lignes (colonnes : nom, date de naissance, téléphone, structure, une quantité par appui, puis les observations de visite)
                    </label>
                    <textarea id="texteColle" x-model="texteColle" rows="4" placeholder="Kouassi Yao&#9;12/03/1980&#9;0701020304&#9;&#9;50"
                              class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-xs"></textarea>
                    <div class="mt-2 flex items-center gap-3">
                        <button type="button" @click="importer()" class="rounded-md bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Importer dans la grille</button>
                        <span class="text-xs text-slate-500" x-text="messageImport"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="w-10 px-2 py-2">N°</th>
                                <th class="px-2 py-2">Nom *</th>
                                <th class="px-2 py-2">Naissance *</th>
                                <th class="px-2 py-2">Téléphone *</th>
                                <th class="px-2 py-2">Structure</th>
                                <template x-for="(appui, k) in appuis" :key="appui.cle">
                                    <th class="px-2 py-2 text-right" x-text="'Qté : ' + libelleAppui(appui) + (uniteDe(appui) ? ' (' + uniteDe(appui) + ')' : '')"></th>
                                </template>
                                <th class="px-2 py-2">Visite de terrain</th>
                                <th class="w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(ligne, i) in lignes" :key="i">
                                <tr class="border-t border-slate-100">
                                    <td class="px-2 py-1 text-slate-400" x-text="i + 1"></td>
                                    <td class="px-1 py-1"><input type="text" maxlength="60" x-model="ligne.nom" :name="`acteurs[${i}][nom]`" :data-cellule="`${i}-0`" @keydown.enter.prevent="suivante(i, 0)" class="w-full min-w-40 rounded-md border-slate-300 text-sm"></td>
                                    <td class="px-1 py-1"><input type="date" x-model="ligne.date_naissance" :name="`acteurs[${i}][date_naissance]`" :data-cellule="`${i}-1`" @keydown.enter.prevent="suivante(i, 1)" class="rounded-md border-slate-300 text-sm"></td>
                                    <td class="px-1 py-1"><input type="tel" inputmode="numeric" x-model="ligne.telephone" :name="`acteurs[${i}][telephone]`" :data-cellule="`${i}-2`" @keydown.enter.prevent="suivante(i, 2)" class="w-36 rounded-md border-slate-300 text-sm"></td>
                                    <td class="px-1 py-1"><input type="text" maxlength="150" x-model="ligne.nom_structure" :name="`acteurs[${i}][nom_structure]`" :data-cellule="`${i}-3`" @keydown.enter.prevent="suivante(i, 3)" class="w-full min-w-36 rounded-md border-slate-300 text-sm"></td>
                                    <template x-for="(appui, k) in appuis" :key="appui.cle">
                                        <td class="px-1 py-1"><input type="number" min="0" step="0.0001" x-model="ligne.quantites[appui.cle]" :name="`acteurs[${i}][quantites][${k}]`" :data-cellule="`${i}-${4 + k}`" @keydown.enter.prevent="suivante(i, 4 + k)" class="w-28 rounded-md border-slate-300 text-right text-sm"></td>
                                    </template>
                                    <td class="px-1 py-1"><input type="text" maxlength="255" x-model="ligne.visite" :name="`acteurs[${i}][visite]`" placeholder="Observations" :data-cellule="`${i}-${4 + appuis.length}`" @keydown.enter.prevent="suivante(i, 4 + appuis.length)" class="w-full min-w-48 rounded-md border-slate-300 text-sm"></td>
                                    <td class="px-1 py-1 text-right"><button type="button" @click="supprimerLigne(i)" class="text-slate-400 hover:text-red-600" title="Retirer la ligne">✕</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <p class="mt-2 text-xs text-red-700" x-show="depasse" x-cloak x-text="`Une série ne peut pas dépasser ${max} acteurs.`"></p>
            </section>

            {{-- Validation --}}
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="soumettre" value="1" x-model="soumettre" @checked(old('soumettre')) class="rounded border-slate-300">
                        Soumettre les demandes aussitôt (sinon elles restent en brouillon)
                    </label>
                    <p class="mt-1 text-xs text-amber-700" x-show="soumettre && sansVisite > 0" x-cloak
                       x-text="`${sansVisite} ligne(s) sans visite de terrain : renseignez-la, ou décochez pour enregistrer en brouillon.`"></p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('demandes.index') }}" class="text-sm text-slate-600 hover:underline">Annuler</a>
                    <button type="submit" :disabled="nbActeurs === 0 || depasse || envoi || (soumettre && sansVisite > 0)"
                            class="rounded-lg bg-[--color-primary] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                        <span x-text="envoi ? 'Enregistrement…' : `Enregistrer ${nbActeurs} demande(s)`"></span>
                    </button>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
