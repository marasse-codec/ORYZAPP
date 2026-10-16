@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Séance du '.$session->date_debut_session?->format('d/m/Y'))

@section('content')
<x-ui.page :titre="'Séance du '.$session->date_debut_session?->format('d/m/Y')" :sous-titre="$session->resume">
    <x-slot:actions>
        @if ($session->estOuverte())
            <x-ui.badge ton="vert">Séance ouverte</x-ui.badge>
            <form action="{{ route('commissions.cloturer', $session->id_session) }}" method="POST"
                  onsubmit="return confirm('Clôturer cette séance ? Les attributions seront validées et ne pourront plus être modifiées.')">
                @csrf
                <x-ui.bouton type="submit" variante="danger">Clôturer</x-ui.bouton>
            </form>
        @else
            <x-ui.badge>Clôturée le {{ $session->date_fin_session?->format('d/m/Y') }}</x-ui.badge>
            @if ($publiable)
                <form action="{{ route('attributions.publier') }}" method="POST"
                      onsubmit="return confirm('Publier les résultats de cette séance ? Les demandeurs pourront être informés et les bons servis.')">
                    @csrf
                    <input type="hidden" name="id_session" value="{{ $session->id_session }}">
                    <x-ui.bouton type="submit">Publier les résultats</x-ui.bouton>
                </form>
            @endif
        @endif
        <x-ui.bouton :href="route('commissions.index')" variante="secondaire">Retour aux séances</x-ui.bouton>
    </x-slot:actions>

    {{-- Demandes à traiter --}}
    <x-ui.card :titre="'Demandes en attente d\'arbitrage ('.$enAttente->count().')'" sans-marge>
        @if ($enAttente->isEmpty())
            <p class="p-4 text-sm text-[--color-muted] sm:p-6">
                {{ $session->estOuverte() ? 'Aucune demande n\'attend d\'arbitrage.' : 'La séance est clôturée.' }}
            </p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3">Demande</th>
                        <th class="px-4 py-3">Demandeur</th>
                        <th class="px-4 py-3">Cycle visé</th>
                        <th class="px-4 py-3">Localités</th>
                        <th class="px-4 py-3 text-center">Appuis</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($enAttente as $demande)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-[--color-ink]">DEM-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $demande->acteur?->nom }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $demande->campagne?->libelleComplet() ?? '-' }}
                                @if ($demande->campagne?->estAVenir())
                                    <x-ui.badge ton="bleu">à venir</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $demande->localites->pluck('lib_localite')->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $demande->lignes->count() }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($session->estOuverte())
                                    <x-ui.bouton :href="route('attributions.create', ['idDemande' => $demande->id_demande, 'idSession' => $session->id_session])" class="px-3 py-1.5 text-xs">Décider</x-ui.bouton>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        @endif
    </x-ui.card>

    {{-- Attributions de la séance --}}
    <x-ui.card :titre="'Attributions de la séance ('.$session->attributions->count().')'" sans-marge>
        @if ($session->attributions->isEmpty())
            <p class="p-4 text-sm text-[--color-muted] sm:p-6">Aucune attribution enregistrée sur cette séance.</p>
        @else
            <x-ui.tableau>
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                    <tr>
                        <th class="px-4 py-3">Attribution</th>
                        <th class="px-4 py-3">Demandeur</th>
                        <th class="px-4 py-3 text-center">Lignes</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-center">Statut</th>
                        <th class="px-4 py-3 text-center">Servie</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[--color-border]">
                    @foreach ($session->attributions as $attribution)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-[--color-ink]">
                                <a href="{{ route('attributions.show', $attribution->id_attribution) }}" class="hover:underline">ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}</a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->nom }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $attribution->lignesAttributions->count() }}</td>
                            <td class="px-4 py-3 text-right text-[--color-ink]">{{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-center"><x-ui.badge :ton="$attribution->statut->ton()">{{ $attribution->statut->libelle() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $attribution->date_destockage?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($session->estOuverte())
                                    <form action="{{ route('attributions.destroy', $attribution->id_attribution) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Annuler cette attribution ?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.bouton type="submit" variante="danger" class="px-2.5 py-1 text-xs">Annuler</x-ui.bouton>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.tableau>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
