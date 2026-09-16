@extends($layout ?? 'layouts.app')

@section('content')

<div class="min-h-screen bg-[--color-background] px-6 py-8">
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
                        DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                    </h1>
                    <p class="text-sm text-slate-500">
                        Déposée le {{ $demande->date_demande?->format('d/m/Y') }} ·
                        Mise en place souhaitée le {{ $demande->debut_periode_mise_en_place?->format('d/m/Y') }}
                    </p>
                </div>
                <span class="inline-block rounded-full px-3 py-1.5 text-xs font-semibold {{ $demande->soumise ? "bg-green-500" : "bg-red-500" }}">
                    {{ $demande->soumise ? "Demande soumise" : "Demande en brouillon" }}
                </span>

                <a href="{{ route('demandes.pdf', $demande->id_demande) }}" 
                    target="_blank"
                    class="inline-flex SQLSTATE[HY000]: General error: 1364 Field 'id_demande' doesn't have a default value (Connection: mysql, Host: aderiz-mysql, Port: 3306, Database: aderiz_app, SQL: insert into `demandes_corrections` (`id_traitement`, `motif`, `date_demande_correction`, `updated_at`, `created_at`) values (1, Modifie, 2026-09-11 11:11:05, 2026-09-11 11:11:05, 2026-09-11 11:11:05))  items-center gap-2 rounded-lg bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                        <span class="material-symbols-outlined text-base">picture_as_pdf</span> Télécharger la fiche PDF (A4)
                </a>
            </div>

            @if ($demande->motifs_rejet)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <p class="text-sm font-semibold text-red-800">
                        Demande rejetée le {{ $demande->date_rejet?->format('d/m/Y') }}
                    </p>
                    <p class="mt-1 text-sm text-red-700">{{ $demande->motifs_rejet }}</p>
                </div>
            @endif
        </div>

        {{-- Demandeur --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Demandeur</h2>
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Nom</dt>
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
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Localité</dt>
                    <dd class="mt-0.5 text-slate-800">
                        {{ $demande->localite?->lib_localite }}
                        <span class="text-slate-500">({{ $demande->localite?->departement?->lib_departement }})</span>
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





        {{-- Appuis --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Types d'appuis sollicités</h2>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="pb-2">Type d'appui</th>
                        <th class="pb-2">Service</th>
                        <th class="pb-2 text-right">Quantité</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($demande->lignes as $ligne)
                        <tr>
                            <td class="py-2 text-slate-800">
                                {{ $ligne->type_appui?->lib_type_appui }}
                                <span class="block text-xs text-slate-500">{{ $ligne->typeAppui?->lib_type_appui }}</span>
                            </td>
                            <td class="py-2 text-slate-600">{{ $ligne->typeAppui?->service?->lib_service }}</td>
                            <td class="py-2 text-right text-slate-800">
                                {{ $ligne->quantite_demandee }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pièces --}}
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







        {{-- Attribution --}}
        @if ($demande->attributions)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-[--color-primary]">Attribution</h2>
                    <a href="{{ route('attributions.bon', $demande->attribution->id_attribution) }}"
                       class="rounded-md bg-[--color-primary] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">
                        Bon d'attribution
                    </a>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="pb-2">Appui</th>
                            <th class="pb-2 text-right">Quantité</th>
                            <th class="pb-2 text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($demande->attribution->lignesAttributions as $ligne)
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
                                {{ number_format($demande->attribution->montantTotal(), 0, ',', ' ') }} F
                            </td>
                        </tr>
                    </tfoot>
                </table>
                @if ($demande->attribution->commentaires)
                    <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        {{ $demande->attribution->commentaires }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Instruction --}}
        @if ($demande->rapports->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Instruction</h2>
                <ul class="space-y-3 text-sm">
                    @foreach ($demande->rapports as $rapport)
                        <li class="rounded-lg border border-slate-200 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">{{ $rapport->service?->lib_service }}</span>
                                <span class="text-xs {{ $rapport->estAccorde() ? 'text-emerald-700' : 'text-orange-700' }}">
                                    {{ $rapport->estAccorde() ? 'Autorisée le '.$rapport->accord_service->format('d/m/Y') : 'En cours' }}
                                </span>
                            </div>
                            @if ($rapport->commentaire)
                                <p class="mt-1 text-slate-600">{{ $rapport->commentaire }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a href="{{ route('demandes.index') }}" class="inline-block text-sm font-medium text-[--color-primary] hover:underline">
            ← Retour à la liste
        </a>
    </div>
</div>
@endsection
