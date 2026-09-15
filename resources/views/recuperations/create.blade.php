@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-3xl mx-auto space-y-6">

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-[--color-primary]">
                Servir le bon ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ $attribution->demande?->acteur?->nom }} · {{ $attribution->demande?->acteur?->telephone }} ·
                Séance clôturée le {{ $attribution->session?->date_fin_session?->format('d/m/Y') }}
            </p>
        </div>

        @include('partials.flash')

        <form action="{{ route('attributions.destocker', $attribution->id_attribution) }}" method="POST"
            enctype="multipart/form-data"
            onsubmit="return confirm('Confirmer la remise ? Le stock sera décrémenté.')">
        @csrf
        @method('PATCH')
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Contenu de l'attribution</h2>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="pb-2">Appui</th>
                        <th class="pb-2 text-right">À servir</th>
                        <th class="pb-2 text-right">En stock</th>
                        <th class="pb-2 text-right">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($attribution->lignesAttributions as $ligne)
                        @php
                            $appui = $ligne->appui;
                            $unite = $ligne->appui?->uniteAppui?->lib_unite_appui;
                            $suffisant = ($ligne->appui?->quantite_en_stock ?? 0) >= $ligne->quantite_attribuee;
                        @endphp
                        <tr>
                            <td class="py-2 text-slate-800">{{ $ligne->appui?->libelle }}</td>
                            <td class="py-2 text-right text-slate-800">
                                {{ $ligne->quantite_attribuee }} <span class="text-slate-500">{{ $unite }}</span>
                            </td>
                            <td class="py-2 text-right {{ $suffisant ? 'text-slate-600' : 'text-red-700 font-semibold' }}">
                                {{ $ligne->appui?->quantite_en_stock }} <span class="text-slate-500">{{ $unite }}</span>
                            </td>
                            <td class="py-2 text-right text-slate-800">{{ number_format($ligne->montant(), 0, ',', ' ') }} F</td>
                        </tr>
                        {{-- Si l'appui est un équipement, afficher le select d'équipements physiques --}}
                        @if ($appui && $appui->equipements->count() > 0)

                            <tr>
                                <td colspan="4" class="bg-slate-50 p-3 rounded-md">
                                    <x-input-label 
                                        for="equipements_{{ $ligne->id_ligne_attribution ?? $index }}" 
                                        value="Sélectionner l'équipement/numéro de série à sortir ({{ $ligne->quantite_attribuee }} requis)" 
                                        class="text-xs font-semibold text-slate-700" 
                                    />

                                    {{-- Cas 1 : Si la quantité attribuée est de 1 (Un simple Select) --}}
                                    @if($ligne->quantite_attribuee == 1)
                                        <select name="equipements[{{ $ligne->id_ligne_attribution }}][]" 
                                                id="equipements_{{ $ligne->id_ligne_attribution }}" 
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            <option value="">-- Choisir l'équipement --</option>
                                            @foreach ($appui->equipements as $equipement)
                                                <option value="{{ $equipement->id_equipement }}"
                                                    {{ in_array($equipement->id_equipement, old("equipements.{$ligne->id_ligne_attribution}", [])) ? 'selected' : '' }}>
                                                    {{ $equipement->matricule_equipement }}
                                                </option>
                                            @endforeach
                                        </select>
                                    
                                    {{-- Cas 2 : Si la quantité attribuée est > 1 (Multiple Select ou Checkboxes) --}}
                                    @else
                                        <select name="equipements[{{ $ligne->id_ligne_attribution }}][]" 
                                                id="equipements_{{ $ligne->id_ligne_attribution }}" 
                                                multiple 
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            @foreach ($appui->equipements as $equipement)
                                                <option value="{{ $equipement->id_equipement }}"
                                                    {{ in_array($equipement->id_equipement, old("equipements.{$ligne->id_ligne_attribution}", [])) ? 'selected' : '' }}>
                                                    {{ $equipement->matricule_equipement }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500">Maintenez CTRL / CMD pour sélectionner plusieurs équipements ({{ $ligne->quantite_attribuee }} minimum).</p>
                                    @endif

                                    <x-input-error :messages="$errors->get('equipements.'.$ligne->id_ligne_attribution)" class="mt-1" />
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-200 font-semibold">
                        <td colspan="3" class="pt-2 text-slate-700">Total</td>
                        <td class="pt-2 text-right text-slate-800">{{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F</td>
                    </tr>
                </tfoot>
            </table>

            @if ($attribution->commentaires)
                <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $attribution->commentaires }}</p>
            @endif
        </div>

        <div class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div>
                <h2 class="text-base font-semibold text-[--color-primary]">Récipiendaire</h2>
                <p class="text-sm text-slate-500">
                    Le retrait par un tiers exige une procuration en plus de sa pièce d'identité.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="recipiendaire" value="Nom du récipiendaire" />
                    <x-text-input id="recipiendaire" name="recipiendaire" type="text" class="mt-1 block w-full"
                                  :value="old('recipiendaire', $attribution->demande?->acteur?->nom)" required />
                    <x-input-error :messages="$errors->get('recipiendaire')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="numero_piece_identite" value="N° de la pièce (facultatif)" />
                    <x-text-input id="numero_piece_identite" name="numero_piece_identite" type="text"
                                  class="mt-1 block w-full" :value="old('numero_piece_identite')" />
                    <x-input-error :messages="$errors->get('numero_piece_identite')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="piece_identite" value="Scan de la pièce d'identité" />
                <input id="piece_identite" name="piece_identite" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                       class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-[--color-primary] file:px-3 file:py-2 file:text-sm file:text-white hover:file:opacity-90">
                <p class="mt-1 text-xs text-slate-500">PDF ou image, 4 Mo maximum. Obligatoire pour valider la sortie.</p>
                <x-input-error :messages="$errors->get('piece_identite')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="date_destockage" value="Date de récupération" />
                <x-text-input id="date_destockage" name="date_destockage" type="date" class="mt-1 block w-full sm:w-64"
                              max="{{ now()->toDateString() }}"
                              :value="old('date_destockage', now()->toDateString())" required />
                <p class="mt-1 text-xs text-slate-500">La validation décrémente le stock de chaque appui du bon.</p>
                <x-input-error :messages="$errors->get('date_destockage')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('recuperations.index') }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Valider la sortie de stock
                </button>
            </div>
        </div>
        </form>
    </div>
</div>
@endsection
