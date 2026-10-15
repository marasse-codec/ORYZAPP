@extends($layout ?? 'layouts.aderiz')

@php
    $numero = 'ATT-'.str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT);
    $reste = $attribution->resteDu();
    $soumis = $attribution->paiements->filter(fn ($p) => $p->estSoumis())->sortBy('date_paiement');
    $brouillons = $attribution->paiements->reject(fn ($p) => $p->estSoumis())->sortBy('id_paiement');
    $f = fn ($v) => number_format($v, 0, ',', ' ').' F';
    $classeChamp = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
@endphp

@section('titre-page', 'Règlement '.$numero)

@section('content')
<x-ui.page :titre="'Règlement de l\'attribution '.$numero"
           :sous-titre="'Demande DEM-'.$attribution->id_demande.' · publiée le '.$attribution->date_publication->format('d/m/Y')">
    <x-slot:actions>
        <x-ui.bouton :href="route('comptabilite.attributions')" variante="secondaire">← Attributions à encaisser</x-ui.bouton>
    </x-slot:actions>

    {{-- Où en est le règlement --}}
    <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-ui.stat libelle="Montant dû" :valeur="$f($attribution->montantTotal())" />
        <x-ui.stat libelle="Réglé (soumis)" ton="vert" :valeur="$f($attribution->totalPaye())" />
        <x-ui.stat libelle="En brouillon" :ton="$brouillons->isNotEmpty() ? 'ambre' : 'neutre'" :valeur="$f($attribution->totalBrouillons())" indice="Pas encore pris en compte" />
        <x-ui.stat libelle="Reste à payer" :ton="$reste > 0 ? 'rouge' : 'vert'" :valeur="$reste > 0 ? $f($reste) : 'Soldé'" />
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card titre="Demandeur">
            <dl class="space-y-1 text-sm">
                <div><dt class="inline text-[--color-muted]">Nom :</dt> <dd class="inline font-medium">{{ $attribution->demande?->acteur?->nom ?? '-' }}</dd></div>
                @if ($attribution->demande?->nom_structure)
                    <div><dt class="inline text-[--color-muted]">Structure :</dt> <dd class="inline">{{ $attribution->demande->nom_structure }}</dd></div>
                @endif
                <div><dt class="inline text-[--color-muted]">Téléphone :</dt> <dd class="inline">{{ $attribution->demande?->acteur?->telephone ?? '-' }}</dd></div>
                <div><dt class="inline text-[--color-muted]">Localité(s) :</dt> <dd class="inline">{{ $attribution->demande?->localites->pluck('lib_localite')->join(', ') ?: '-' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card titre="Appuis attribués" sans-marge>
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr><th class="px-4 py-2">Appui</th><th class="px-4 py-2 text-right">Quantité</th><th class="px-4 py-2 text-right">Montant</th></tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($attribution->lignesAttributions as $ligne)
                        <tr>
                            <td class="px-4 py-2">{{ $ligne->appui?->libelle ?? 'Appui' }}@if ((float) $ligne->remise > 0) <span class="text-xs text-[--color-muted]">(remise {{ (float) $ligne->remise }} %)</span>@endif</td>
                            <td class="px-4 py-2 text-right">{{ \App\Utils\Helper::quantite($ligne->quantite_attribuee) }} {{ $ligne->appui?->uniteDosage?->lib_unite }}</td>
                            <td class="px-4 py-2 text-right">{{ $f($ligne->montant()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        </x-ui.card>
    </div>

    {{-- 1. Saisir un règlement : il part en brouillon --}}
    @if ($reste > 0)
        <x-ui.card titre="Nouveau règlement">
            <p class="mb-3 text-sm text-[--color-muted]">Le règlement est d'abord enregistré en <strong>brouillon</strong> : vérifiez-le, puis soumettez-le pour qu'il soit pris en compte.
                Les champs marqués <span class="text-red-600">*</span> sont obligatoires.</p>
            <form action="{{ route('comptabilite.reglement.creer', $attribution->id_attribution) }}" method="POST" class="grid gap-3 sm:grid-cols-4">
                @csrf
                <div>
                    <x-input-label for="montant" value="Montant (F)" obligatoire class="text-xs" />
                    {{-- On propose ce qui reste à payer, moins ce qui attend déjà en brouillon. --}}
                    <input id="montant" name="montant" type="number" step="0.01" min="0.01" max="{{ $reste }}" value="{{ old('montant', max(0, $reste - $attribution->totalBrouillons()) ?: $reste) }}" required class="{{ $classeChamp }}">
                    <x-input-error :messages="$errors->get('montant')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date_paiement" value="Date du règlement" obligatoire class="text-xs" />
                    <input id="date_paiement" name="date_paiement" type="date" max="{{ today()->toDateString() }}" value="{{ old('date_paiement', today()->toDateString()) }}" required class="{{ $classeChamp }}">
                    <x-input-error :messages="$errors->get('date_paiement')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="mode" value="Mode" obligatoire class="text-xs" />
                    <select id="mode" name="mode" required class="{{ $classeChamp }}">
                        @foreach ($modes as $mode)
                            <option value="{{ $mode->value }}" @selected(old('mode') === $mode->value)>{{ $mode->libelle() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('mode')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="reference" value="Référence (reçu, transaction…)" class="text-xs" />
                    <input id="reference" name="reference" type="text" maxlength="100" value="{{ old('reference') }}" class="{{ $classeChamp }}">
                    <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                </div>
                <div class="sm:col-span-4">
                    <x-ui.bouton type="submit">Enregistrer en brouillon</x-ui.bouton>
                </div>
            </form>
        </x-ui.card>
    @endif

    {{-- 2. Les brouillons : corriger, supprimer ou soumettre --}}
    @if ($brouillons->isNotEmpty())
        <x-ui.card titre="Règlements en brouillon" sans-marge>
            <ul class="divide-y divide-[--color-border]">
                @foreach ($brouillons as $paiement)
                    <li class="space-y-3 px-4 py-4 sm:px-6" x-data="{ modifier: false }">
                        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                            <span>
                                <span class="font-semibold text-[--color-ink]">{{ $f($paiement->montant) }}</span>
                                · {{ $paiement->date_paiement->format('d/m/Y') }} · {{ $paiement->modeEnum()?->libelle() ?? $paiement->mode }}
                                @if ($paiement->reference)<span class="text-xs text-[--color-muted]">(réf. {{ $paiement->reference }})</span>@endif
                                <x-ui.badge ton="ambre">Brouillon</x-ui.badge>
                            </span>
                            <span class="flex flex-wrap gap-2">
                                <form action="{{ route('comptabilite.reglement.soumettre', $paiement) }}" method="POST"
                                      onsubmit="return confirm('Soumettre ce règlement ? Il deviendra définitif.')">
                                    @csrf
                                    <x-ui.bouton type="submit">Soumettre</x-ui.bouton>
                                </form>
                                <x-ui.bouton variante="secondaire" @click="modifier = !modifier">Modifier</x-ui.bouton>
                                <form action="{{ route('comptabilite.reglement.supprimer', $paiement) }}" method="POST"
                                      onsubmit="return confirm('Supprimer ce brouillon ?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.bouton type="submit" variante="danger">Supprimer</x-ui.bouton>
                                </form>
                            </span>
                        </div>

                        <form x-show="modifier" x-cloak action="{{ route('comptabilite.reglement.modifier', $paiement) }}" method="POST" class="grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-5">
                            @csrf
                            @method('PUT')
                            <input name="montant" type="number" step="0.01" min="0.01" value="{{ (float) $paiement->montant }}" required aria-label="Montant" class="{{ $classeChamp }}">
                            <input name="date_paiement" type="date" max="{{ today()->toDateString() }}" value="{{ $paiement->date_paiement->toDateString() }}" required aria-label="Date" class="{{ $classeChamp }}">
                            <select name="mode" required aria-label="Mode" class="{{ $classeChamp }}">
                                @foreach ($modes as $mode)
                                    <option value="{{ $mode->value }}" @selected($paiement->mode === $mode->value)>{{ $mode->libelle() }}</option>
                                @endforeach
                            </select>
                            <input name="reference" type="text" maxlength="100" value="{{ $paiement->reference }}" placeholder="Référence" aria-label="Référence" class="{{ $classeChamp }}">
                            <x-ui.bouton type="submit">Enregistrer</x-ui.bouton>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- 3. L'historique des règlements soumis --}}
    <x-ui.card titre="Règlements soumis" sans-marge>
        @if ($soumis->isEmpty())
            <p class="p-4 text-sm text-[--color-muted] sm:p-6">Aucun règlement soumis pour cette attribution.</p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Date</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 sm:px-6">Soumis par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($soumis as $paiement)
                        <tr>
                            <td class="px-4 py-3 sm:px-6">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $paiement->modeEnum()?->libelle() ?? $paiement->mode }}</td>
                            <td class="px-4 py-3">{{ $paiement->reference ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ $f($paiement->montant) }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $paiement->soumisPar?->name ?? '-' }} <span class="text-xs text-[--color-muted]">le {{ $paiement->date_soumission->format('d/m/Y H:i') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
