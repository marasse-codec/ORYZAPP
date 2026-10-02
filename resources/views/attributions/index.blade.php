@php
    use App\Enums\PermissionEnum;
    use App\Enums\RoleEnum;
@endphp
@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-6xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Attributions</h1>
                <p class="text-sm text-slate-500">{{ $attributions->count() }} bon(s) d'attribution</p>
            </div>
            </div>
        </div>

        @include('partials.flash')

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
                        <th class="px-4 py-3 text-center">Actions</th>
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
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('attributions.show', $attribution->id_attribution) }}"
                                       class="text-sm font-medium text-[--color-primary] hover:underline">Lire</a>

                                       
                                       @if (auth()->user()->peutModifierAttribution() && $attribution->estModifiable())
                                            <a href="{{ route('attributions.edit', $attribution->id_attribution) }}"
                                            class="text-sm font-medium text-[--color-button-secondary] hover:underline">Modifier</a>
                                       @endif
                                       
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Aucune attribution enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
