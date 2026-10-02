@php
    use App\Enums\RoleEnum;
@endphp
@extends($layout ?? 'layouts.app')

@section('content')

<div class="min-h-screen bg-[--color-secondary] px-6 py-8">
    <div class="max-w-4xl mx-auto space-y-6">

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- En-tête --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">
                        Demande {{str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Déposée le {{ $demande->date_demande?->format('d/m/Y') }} · <br>
                        Début de mise en place souhaité : <strong>{{ $demande->periode_debut_mise_en_place?->format('d/m/Y') }}</strong> <br>
                        Fin de mise en place espérée : <strong>{{ $demande->periode_fin_mise_en_place?->format('d/m/Y') }}</strong>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-block rounded-full px-3 py-1.5 text-xs font-semibold text-white {{ $demande->estSoumise() ? 'bg-emerald-600' : 'bg-[--color-label-brouillon]' }}">
                        {{ $demande->estSoumise() ? 'Demande soumise' : 'Demande en brouillon' }}
                    </span>

                    <a href="{{ route('demandes.pdf', $demande->id_demande) }}" 
                       target="_blank"
                       class="inline-flex items-center gap-2 rounded-lg bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-base">picture_as_pdf</span> Imprimer la demande
                    </a>
                </div>
            </div>

            @if ($demande->motifs_rejet && ($demande->sessionCommission?->estFinie() || auth()->user()->hasRole(RoleEnum::agentCommission)))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <p class="text-sm font-semibold text-red-800">
                       Motif de rejet
                    </p>
                    <p class="mt-1 text-sm text-red-700">{{ $demande->motifs_rejet }}</p>
                </div>
            @endif
        </div>

        {{-- Demandeur --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Demandeur</h2>
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                @if ($demande->nom_structure)
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Nom de la structure</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $demande->nom_structure }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Représentant / Nom</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->acteur?->nom }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Profil</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->typeActeur?->lib_type_acteur }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Téléphone</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->acteur?->telephone }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">E-mail</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->acteur?->email ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Localité(s) concernée(s)</dt>
                    <dd class="mt-1.5 flex flex-wrap gap-1.5">
                        @forelse ($demande->localites as $localite)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 border border-slate-200">
                                {{ $localite->lib_localite }} 
                                <span class="text-slate-400 ml-1">({{ $localite->departement?->lib_departement }})</span>
                            </span>
                        @empty
                            <span class="text-slate-400 italic">Aucune localité renseignée</span>
                        @endforelse
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Superficies --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Systèmes de production</h2>
            @if ($demande->systemesProductions->isEmpty())
                <p class="text-sm text-slate-500">Aucune superficie déclarée.</p>
            @else
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="pb-2">Système</th>
                            <th class="pb-2 text-right">Superficie (ha)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($demande->systemesProductions as $systeme)
                            <tr>
                                <td class="py-2 text-slate-800">{{ $systeme->typeSystemeProduction?->lib_type_systeme_production }}</td>
                                <td class="py-2 text-right text-slate-800">{{ number_format($systeme->superficie, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 font-semibold">
                            <td class="pt-2 text-slate-700">Total</td>
                            <td class="pt-2 text-right text-slate-800">
                                {{ number_format($demande->systemesProductions->sum('superficie'), 2, ',', ' ') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- Pièces justificatives --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-[--color-primary]">Pièces justificatives</h2>
                @if ($demande->piecesDemandes->isNotEmpty())
                    <a href="{{ route('demandes.pieces', $demande->id_demande) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-md border border-[--color-primary] px-3 py-1.5 text-xs font-semibold text-[--color-primary] hover:bg-emerald-50">
                        <span class="material-symbols-outlined text-sm">print</span> Imprimer les pièces
                    </a>
                @endif
            </div>
            @if ($demande->piecesDemandes->isEmpty())
                <p class="text-sm text-slate-500">Aucune pièce jointe.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($demande->piecesDemandes as $piece)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <span class="text-slate-800">{{ $piece->typePiece?->lib_type_piece }}</span>
                            <a href="{{ $piece->url() }}" target="_blank" rel="noopener"
                               class="text-[--color-primary] font-medium hover:underline">Ouvrir</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Appuis sollicités --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Types d'appuis sollicités</h2>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="pb-2">Type d'appui</th>
                        <th class="pb-2">Service en charge</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($demande->lignes as $ligne)
                        <tr>
                            <td class="py-2 font-medium text-slate-800">
                                {{ $ligne->typeAppui?->lib_type_appui }}
                            </td>
                            <td class="py-2 text-slate-600">
                                {{ $ligne->typeAppui?->service?->lib_service ?? 'Non assigné' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Attributions --}}
        @if ($demande->attributions->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                <h2 class="text-base font-semibold text-[--color-primary]">Attributions accordées</h2>

                @foreach ($demande->attributions as $attribution)
                    <div class="border-t border-slate-100 pt-4 first:border-0 first:pt-0">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="pb-2">Appui</th>
                                    <th class="pb-2 text-right">Quantité</th>
                                    <th class="pb-2 text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($attribution->lignesAttributions as $ligne)
                                    <tr>
                                        <td class="py-2 text-slate-800">{{ $ligne->appui?->libelle }}</td>
                                        <td class="py-2 text-right text-slate-800">{{ $ligne->quantite_attribuee }}</td>
                                        <td class="py-2 text-right text-slate-800">{{ number_format($ligne->montant(), 0, ',', ' ') }} F</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-slate-200 font-semibold">
                                    <td colspan="2" class="pt-2 text-slate-700">Total</td>
                                    <td class="pt-2 text-right text-slate-800">
                                        {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        @if ($attribution->commentaires)
                            <p class="mt-3 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ $attribution->commentaires }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="pt-2">
            <a href="{{ route('demandes.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-[--color-primary] hover:underline">
                ← Retour à la liste des demandes
            </a>
        </div>
    </div>
</div>
@endsection