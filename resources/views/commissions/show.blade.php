@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- En-tête de séance --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">
                        Séance du {{ $session->date_debut_session?->format('d/m/Y') }}
                    </h1>
                    @if ($session->resume)
                        <p class="mt-1 text-sm text-slate-600">{{ $session->resume }}</p>
                    @endif
                </div>

                @if ($session->estOuverte())
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                            Session d'arbitrage active
                        </span>
                        <form action="{{ route('commissions.cloturer', $session->id_session) }}" method="POST"
                              onsubmit="return confirm('Clôturer définitivement cette séance ?')">
                            @csrf
                            <button type="submit"
                                    class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                Clôturer
                            </button>
                        </form>
                    </div>
                @else
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                        Clôturée le {{ $session->date_fin_session?->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        @include('partials.flash')

        {{-- Attributions à arbitrer --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">
                Attributions en attente d'arbitrage ({{ $enAttente->count() }})
            </h2>

            @if ($enAttente->isEmpty())
                <p class="text-sm text-slate-500">
                    {{ $session->estOuverte()
                        ? 'Aucun dossier instruit n\'attend d\'arbitrage.'
                        : 'La séance est clôturée.' }}
                </p>
            @else
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="pb-2">Dossier</th>
                            <th class="pb-2">Demandeur</th>
                            <th class="pb-2">Localité</th>
                            <th class="pb-2 text-center">Appuis</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($enAttente as $attribution)
                            <tr>
                                <td class="py-2 font-medium text-slate-800">
                                    DEM-{{ $attribution->demande->date_demande?->format('Y') }}-{{ str_pad($attribution->demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-2 text-slate-600">{{ $attribution->demande->acteur?->nom }}</td>
                                <td class="py-2 text-slate-600">{{ $attribution->demande->localite?->lib_localite }}</td>
                                <td class="py-2 text-center text-slate-600">{{ $attribution->demande->lignes->count() }}</td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('attributions.show', $attribution->id_attribution) }}"
                                       class="rounded-md bg-[--color-primary] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">
                                        Traiter
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Attributions arbitrés --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">
                Attributions de la séance ({{ $session->attributions->count() }})
            </h2>

            @if ($session->attributions->isEmpty())
                <p class="text-sm text-slate-500">Aucune attribution enregistrée sur cette séance.</p>
            @else
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="pb-2">Dossier</th>
                            <th class="pb-2">Demandeur</th>
                            <th class="pb-2 text-center">Lignes</th>
                            <th class="pb-2 text-right">Montant</th>
                            <th class="pb-2 text-center">Statut</th>
                            <th class="pb-2 text-center">Servie</th>
                            <th class="pb-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($session->attributions as $attribution)
                            <tr>
                                <td class="py-2 font-medium text-slate-800">
                                    ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-2 text-slate-600">{{ $attribution->demande?->acteur?->nom }}</td>
                                <td class="py-2 text-center text-slate-600">{{ $attribution->lignesAttributions->count() }}</td>
                                <td class="py-2 text-right text-slate-800">
                                    {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F
                                </td>
                                <td class="py-2 text-center">
                                    @if($attribution->id_statut_attribution)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $attribution->id_statut_attribution->couleur() }}">
                                            {{ $attribution->id_statut_attribution->libelle() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 text-center text-slate-600">
                                    {{ $attribution->date_destockage?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="py-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Bouton Valider --}}
                                        <form action="{{ route('attributions.valider') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id_attribution" value="{{ $attribution->id_attribution }}">
                                            <button type="submit" 
                                                    class="rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
                                                    onclick="return confirm('Valider cette attribution ?')">
                                                Valider
                                            </button>
                                        </form>

                                        {{-- Bouton Annuler --}}
                                        <form action="{{ route('attributions.annuler') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id_attribution" value="{{ $attribution->id_attribution }}">
                                            <button type="submit" 
                                                    class="rounded-md border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100"
                                                    onclick="return confirm('Annuler cette attribution ?')">
                                                Annuler
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <a href="{{ route('commissions.index') }}" class="inline-block text-sm font-medium text-[--color-primary] hover:underline">
            ← Retour aux sessions
        </a>
    </div>
</div>
@endsection