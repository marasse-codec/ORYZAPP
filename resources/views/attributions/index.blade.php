@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-6xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Attributions</h1>
                <p class="text-sm text-slate-500">{{ $attributions->count() }} bon(s) d'attribution</p>
            </div>
            @can(\App\Enums\PermissionEnum::gererCommission->value)
                <form action="{{ route('attributions.publier') }}"
                    method="POST"
                    class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-md bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
                            onclick="return confirm('Voulez vous publier')">
                        <span class="material-symbols-outlined text-base">add</span> Publier
                    </button>
                </form>
            @endcan
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
                        <th class="px-4 py-3 text-center">Lignes</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-center">Déstockage</th>
                        <th class="px-4 py-3"></th>
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
                                       class="text-sm font-medium text-[--color-primary] hover:underline">Ouvrir</a>

                                       @if($actions)
                                        {{-- Soumettre une attribution --}}
                                            @if(!$attribution->session)
                                                <form action="{{ route($actions['soumettre']['lien']) }}"
                                                    method="POST"
                                                    class="inline">
                                                    @csrf
                                                    <input hidden name=id_attribution value="{{$attribution->id_attribution}}">
                                                    {{----}}
                                                    @if($sessionOuverte)
                                                        <button type="submit"
                                                                class="text-sm font-semibold text-emerald-700 hover:underline"
                                                                onclick="return confirm('Voulez-vous confirmer la validation de cette attribution, Elle sera traitée au nom de la séance de la commission du {{$sessionOuverte->date_debut_session}} ?')">
                                                            {{$actions['soumettre']['libelle']}}
                                                        </button>
                                                    @elseif ($attribution->submissible)
                                                        <button type="submit"
                                                                class="text-sm font-semibold text-emerald-700 hover:underline"
                                                                onclick="return confirm('Voulez-vous confirmer la soumission de cette attribution ?')">
                                                            {{$actions['soumettre']['libelle']}}
                                                        </button>
                                                    @endif
                                                </form>
                                            @endif
                                            {{-- Supprimer une attribution --}}
                                            @unless ($attribution->date_destockage)
                                                <form action="{{ route($actions['supprimer']['lien'], $attribution->id_attribution) }}"
                                                    method="POST" onsubmit="return confirm('Retirer cette attribution ?')">
                                                    @csrf
                                                    <input hidden name=id_attribution value="{{$attribution->id_attribution}}">
                                                    @method('DELETE')
                                                    
                                                    @if($sessionOuverte)
                                                    <button type="submit" 
                                                        onclick="return confirm('Voulez-vous confirmer l\'annulation de cette attribution, Elle sera traitée au nom de la séance de la commission du {{$sessionOuverte->date_debut_session}} ?')"
                                                    class="text-sm font-medium text-red-700 hover:underline">{{$actions['supprimer']['libelle']}}</button>
                                                    @endif
                                                </form>
                                            @endunless
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
