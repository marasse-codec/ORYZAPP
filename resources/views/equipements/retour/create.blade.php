@extends($layout ?? 'layouts.aderiz')

@section('content')
<div
    x-data="{
        idEquipement: '',
        etat: '',
        equipementsIndex: {{ Illuminate\Support\Js::from(
            $equipements->mapWithKeys(fn ($e) => [
                $e->id_equipement => [
                    'matricule' => $e->matricule_equipement,
                    'etat'      => $e->etat,
                    'appui'     => $e->appui?->libelle ?? '—',
                ]
            ])
        ) }},

        get infosEquipement() {
            return this.idEquipement ? (this.equipementsIndex[this.idEquipement] ?? null) : null;
        },
    }"
    class="min-h-screen bg-[--color-background] text-slate-800 pb-16"
>
    <header class="bg-white border-b border-slate-200 px-6 py-5 shadow-sm">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-[--color-primary]">Retour d'équipement</h1>
            <p class="text-xs text-slate-500">Agence pour le Développement de la Filière Riz (ADERIZ)</p>
        </div>
    </header>

    <div class="max-w-2xl mx-auto px-6 mt-6">

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">Le retour n'a pas pu être enregistré :</p>
                <ul class="mt-1.5 list-disc list-inside text-sm text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('equipements.retour.store') }}"
              class="bg-white rounded-xl shadow-sm border border-slate-200">
            @csrf

            <div class="p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-[--color-primary]">Équipement retourné</h2>
                    <p class="text-sm text-slate-500">Sélectionnez l'équipement et précisez son état au retour.</p>
                </div>

                {{-- Sélection de l'équipement --}}
                <div>
                    <x-input-label for="id_equipement" value="Équipement" />
                    <select id="id_equipement" name="id_equipement" required
                            x-model="idEquipement"
                            class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm">
                        <option value="">— Sélectionner un équipement —</option>
                        @foreach ($equipements as $equipement)
                            <option value="{{ $equipement->id_equipement }}"
                                    @selected(old('id_equipement') == $equipement->id_equipement)>
                                {{ $equipement->matricule_equipement }}
                                — {{ $equipement->appui?->libelle ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('id_equipement')" class="mt-1" />
                </div>

                {{-- Fiche récapitulative (visible dès qu'un équipement est sélectionné) --}}
                <div x-show="infosEquipement" x-cloak
                     class="rounded-lg border border-slate-200 bg-slate-50 p-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Matricule</p>
                        <p class="font-semibold text-slate-800 mt-0.5" x-text="infosEquipement?.matricule ?? '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Appui associé</p>
                        <p class="font-semibold text-slate-800 mt-0.5" x-text="infosEquipement?.appui ?? '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">État au départ</p>
                        <p class="font-semibold text-slate-800 mt-0.5" x-text="infosEquipement?.etat ?? '—'"></p>
                    </div>
                </div>

                {{-- État au retour --}}
                <div>
                    <x-input-label value="État au retour" />
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        @foreach ([
                            'Bon état'    => ['icone' => 'check_circle',  'couleur' => 'emerald'],
                            'Abîmé'       => ['icone' => 'warning',       'couleur' => 'amber'],
                            'Défectueux'  => ['icone' => 'cancel',        'couleur' => 'red'],
                        ] as $valeur => $config)
                            <label class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                                   :class="etat === '{{ $valeur }}'
                                       ? 'border-[--color-primary] bg-emerald-50'
                                       : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="etat" value="{{ $valeur }}"
                                       x-model="etat"
                                       @checked(old('etat') === $valeur)
                                       class="text-[--color-primary] focus:ring-[--color-primary]">
                                <span class="flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                    <span class="material-symbols-outlined text-{{ $config['couleur'] }}-500 text-base">
                                        {{ $config['icone'] }}
                                    </span>
                                    {{ $valeur }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('etat')" class="mt-1" />
                </div>
            </div>

            {{-- Navigation --}}
            <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4">
                <a href="{{ route('equipements.index') }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Annuler
                </a>

                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                        :disabled="!idEquipement || !etat">
                    Enregistrer le retour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection