@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Séances de commission')

@section('content')
<x-ui.page titre="Séances de commission" sous-titre="Une séance ouverte accueille les attributions ; sa clôture les valide, puis on publie les résultats.">
    <x-slot:actions>
        <x-ui.bouton :href="route('commissions.create')">
            <span class="material-symbols-outlined text-base">add</span> Ouvrir une séance
        </x-ui.bouton>
    </x-slot:actions>

    <x-ui.card sans-marge>
        <x-ui.tableau>
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-[--color-muted]">
                <tr>
                    <th class="px-4 py-3">Séance</th>
                    <th class="px-4 py-3 text-center">Attributions</th>
                    <th class="px-4 py-3 text-center">État</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[--color-border]">
                @forelse ($sessions as $session)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-[--color-ink]">Séance du {{ $session->date_debut_session?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $session->attributions_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($session->estOuverte())
                                <x-ui.badge ton="vert">Ouverte</x-ui.badge>
                            @else
                                <x-ui.badge>Clôturée le {{ $session->date_fin_session?->format('d/m/Y') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('commissions.show', $session->id_session) }}" class="text-sm font-medium text-[--color-primary] hover:underline">Lire</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-[--color-muted]">Aucune séance enregistrée.</td></tr>
                @endforelse
            </tbody>
        </x-ui.tableau>
    </x-ui.card>
</x-ui.page>
@endsection
