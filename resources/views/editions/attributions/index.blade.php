@php
    use App\Enums\PermissionEnum;
    use App\Enums\RoleEnum;
    use App\Exports\AttributionsExport;

    // Colonnes à choisir pour exports
    $catalogueColonnes = AttributionsExport::catalogueColonnes();
@endphp
@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8" x-data="{ modalExport: false }">
    <div class="max-w-6xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Attributions</h1>
                <p class="text-sm text-slate-500">{{ $attributions->count() }} bon(s) d'attribution</p>
            </div>

            {{-- Bouton Déclencheur du Modal d'Exportation --}}
            <button type="button" @click="modalExport = true" 
                    class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm">
                <span class="material-symbols-outlined text-lg">download_for_offline</span>
                Exporter les attributions (Excel)
            </button>
        </div>

        @include('partials.flash')

        {{-- Modal de Configuration de l'Exportation Excel --}}
        <div x-show="modalExport" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4"
             x-transition>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-2xl p-6 space-y-6"
                 @click.outside="modalExport = false">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-[--color-primary] flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">tune</span>
                        Configurer l'exportation Excel
                    </h3>
                    <button type="button" @click="modalExport = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <form action="{{ route('stats.export.attributions') }}" method="GET" class="space-y-6"
                      x-data="{
                          colonnes: {{ Illuminate\Support\Js::from(array_keys($catalogueColonnes)) }},
                          toutesColonnes: {{ Illuminate\Support\Js::from(array_keys($catalogueColonnes)) }},
                          toggleAll() {
                              if (this.colonnes.length === this.toutesColonnes.length) {
                                  this.colonnes = [];
                              } else {
                                  this.colonnes = [...this.toutesColonnes];
                              }
                          }
                      }">
                    
                    {{-- 1. Période de date --}}
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 tracking-wider mb-2">Période d'attribution</label>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs text-slate-600 mb-1">Date début</span>
                                <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                                       class="w-full text-sm border-slate-300 rounded-lg focus:border-emerald-600 focus:ring-emerald-600">
                            </div>
                            <div>
                                <span class="block text-xs text-slate-600 mb-1">Date fin</span>
                                <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                                       class="w-full text-sm border-slate-300 rounded-lg focus:border-emerald-600 focus:ring-emerald-600">
                            </div>
                        </div>
                    </div>

                    {{-- 2. Sélection des colonnes --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Colonnes à inclure</label>
                            <button type="button" @click="toggleAll()" class="text-xs font-semibold text-emerald-700 hover:underline">
                                <span x-text="colonnes.length === toutesColonnes.length ? 'Tout décocher' : 'Tout cocher'"></span>
                            </button>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-2 bg-slate-50 p-4 rounded-xl border border-slate-200 max-h-60 overflow-y-auto">
                            @foreach ($catalogueColonnes as $cle => $libelle)
                                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer p-1 hover:bg-slate-100 rounded-md">
                                    <input type="checkbox" name="colonnes[]" value="{{ $cle }}"
                                           x-model="colonnes"
                                           class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>{{ $libelle }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="modalExport = false"
                                class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 border border-slate-300 hover:bg-slate-50">
                            Annuler
                        </button>
                        <button type="submit" @click="modalExport = false"
                                class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-5 py-2 rounded-xl shadow-sm transition-colors">
                            <span class="material-symbols-outlined text-base">download</span>
                            Télécharger l'export
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tableau d'affichage classique --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Bon</th>
                        <th class="px-4 py-3">Demandeur</th>
                        <th class="px-4 py-3">Séance</th>
                        <th class="px-4 py-3">Date Publication</th>
                        <th class="px-4 py-3 text-center">Lignes</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-center">Déstockage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attributions as $attribution)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->nom }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $attribution->session?->date_debut_session?->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $attribution->date_publication?->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $attribution->lignesAttributions->count() }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">
                                {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($attribution->date_destockage)
                                    <span class="rounded-full bg-green-600 px-2.5 py-1 text-xs font-semibold text-white">
                                        Servie le {{ $attribution->date_destockage->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">En attente</span>
                                @endif
                            </td>
                            
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Aucune attribution enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection