@extends($layout ?? 'layouts.aderiz')

@section('content')
<div
    x-data="{
        equipementsIndex: {{ Illuminate\Support\Js::from(
            $equipements->mapWithKeys(fn ($e) => [
                $e->id_equipement => [
                    'matricule' => $e->matricule_equipement,
                    'etat'      => $e->etat,
                    'appui'     => $e->appui?->libelle ?? '—',
                ]
            ])
        ) }},

        {{-- Lignes dynamiques (récupère l'ancien 'old' en cas d'erreur de validation) --}}
        lignes: {{ Illuminate\Support\Js::from(
            old('retours', [
                ['id_equipement' => '', 'etat' => 'Bon état']
            ])
        ) }},

        ajouterLigne() {
            this.lignes.push({ id_equipement: '', etat: 'Bon état' });
        },

        supprimerLigne(index) {
            if (this.lignes.length > 1) {
                this.lignes.splice(index, 1);
            }
        },

        infosEquipement(id) {
            return id ? (this.equipementsIndex[id] ?? null) : null;
        },

        // Empêche la sélection en double du même équipement
        estEpuise(idEquipement, indexActuel) {
            return this.lignes.some((ligne, idx) => idx !== indexActuel && String(ligne.id_equipement) === String(idEquipement));
        },

        get formValide() {
            return this.lignes.length > 0 && this.lignes.every(l => l.id_equipement && l.etat);
        }
    }"
    class="min-h-screen bg-[--color-background] text-slate-800 pb-16"
>
    <header class="bg-white border-b border-slate-200 px-6 py-5 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Retour multiple d'équipements</h1>
                <p class="text-xs text-slate-500">Agence pour le Développement de la Filière Riz (ADERIZ)</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                <span x-text="lignes.length"></span> &nbsp;équipement(s) dans la liste
            </span>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 mt-6">

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">Des erreurs sont survenues :</p>
                <ul class="mt-1.5 list-disc list-inside text-sm text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('equipements.retour.store') }}" class="space-y-6">
            @csrf

            {{-- Liste répétable des équipements --}}
            <div class="space-y-4">
                <template x-for="(ligne, index) in lignes" :key="index">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative transition-all duration-150 hover:border-slate-300">
                        
                        {{-- En-tête de ligne + bouton supprimer --}}
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Équipement #<span x-text="index + 1"></span>
                            </span>
                            <button type="button" 
                                    @click="supprimerLigne(index)"
                                    x-show="lignes.length > 1"
                                    class="text-xs font-medium text-red-600 hover:text-red-800 flex items-center gap-1 transition">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                Retire de la liste
                            </button>
                        </div>

                        <div class="grid gap-6 md:grid-cols-12">
                            
                            {{-- Sélection de l'équipement (7 cols) --}}
                            <div class="md:col-span-7 space-y-4">
                                <div>
                                    <label :for="'id_equipement_' + index" class="block font-medium text-sm text-gray-700">
                                        Équipement
                                    </label>
                                    <select :id="'id_equipement_' + index" 
                                            :name="'retours[' + index + '][id_equipement]'" 
                                            required
                                            x-model="ligne.id_equipement"
                                            class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm text-sm">
                                        <option value="">— Sélectionner un équipement —</option>
                                        @foreach ($equipements as $equipement)
                                            <option value="{{ $equipement->id_equipement }}"
                                                    :disabled="estEpuise('{{ $equipement->id_equipement }}', index)">
                                                {{ $equipement->matricule_equipement }} — {{ $equipement->appui?->libelle ?? '—' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Fiche récapitulative contextuelle --}}
                                <div x-show="infosEquipement(ligne.id_equipement)" x-cloak
                                     class="rounded-lg border border-slate-200 bg-slate-50 p-3 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <p class="text-slate-400 font-medium uppercase">Appui associé</p>
                                        <p class="font-semibold text-slate-700 mt-0.5" x-text="infosEquipement(ligne.id_equipement)?.appui ?? '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 font-medium uppercase">État au départ</p>
                                        <p class="font-semibold text-slate-700 mt-0.5" x-text="infosEquipement(ligne.id_equipement)?.etat ?? '—'"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- État au retour (5 cols) --}}
                            <div class="md:col-span-5">
                                <x-input-label value="État au retour" />
                                <div class="mt-2 space-y-2">
                                    @foreach ([
                                        'Bon état'   => ['icone' => 'check_circle', 'couleur' => 'emerald'],
                                        'Abîmé'      => ['icone' => 'warning',      'couleur' => 'amber'],
                                        'Défectueux' => ['icone' => 'cancel',       'couleur' => 'red'],
                                    ] as $valeur => $config)
                                        <label class="flex items-center gap-2.5 rounded-lg border p-2.5 cursor-pointer text-xs font-medium transition-colors"
                                               :class="ligne.etat === '{{ $valeur }}'
                                                   ? 'border-[--color-primary] bg-emerald-50 text-[--color-primary]'
                                                   : 'border-slate-200 text-slate-700 hover:border-slate-300'">
                                            <input type="radio" 
                                                   :name="'retours[' + index + '][etat]'" 
                                                   value="{{ $valeur }}"
                                                   x-model="ligne.etat"
                                                   class="text-[--color-primary] focus:ring-[--color-primary]">
                                            <span class="material-symbols-outlined text-{{ $config['couleur'] }}-500 text-base">
                                                {{ $config['icone'] }}
                                            </span>
                                            <span>{{ $valeur }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                </template>
            </div>

            {{-- Bouton d'ajout de ligne --}}
            <div class="flex justify-center">
                <button type="button" 
                        @click="ajouterLigne()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-dashed border-slate-300 text-slate-600 font-semibold text-sm hover:border-[--color-primary] hover:text-[--color-primary] transition bg-white w-full justify-center">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    Ajouter un autre équipement au retour
                </button>
            </div>

            {{-- Navigation et Actions --}}
            <div class="flex items-center justify-between border-t border-slate-200 bg-white rounded-xl shadow-sm p-4 mt-6">
                <a href="{{ route('equipements.index') }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Annuler
                </a>

                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-6 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50 transition"
                        :disabled="!formValide">
                    Enregistrer les retours (<span x-text="lignes.length"></span>)
                </button>
            </div>
        </form>
    </div>
</div>
@endsection