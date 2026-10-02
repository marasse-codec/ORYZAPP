@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-6xl mx-auto">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[--color-primary]">Sorties de stock</h1>
            <p class="text-sm text-slate-500">Bons issus de séances clôturées, en attente de remise au demandeur.</p>
        </div>

        @include('partials.flash')

        {{-- Le récipiendaire se présente avec son bon ou son seul téléphone. --}}
        <form method="GET" action="{{ route('enlevements.index') }}"
              class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <label for="recherche" class="block text-sm font-medium text-slate-700">Rechercher un bon</label>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input id="recherche" name="recherche" type="search" value="{{ $recherche }}"
                       placeholder="N° de bon (ex. 12), téléphone ou nom du demandeur"
                       class="flex-1 border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm text-sm">
                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Rechercher
                </button>
                @if ($recherche !== '')
                    <a href="{{ route('enlevements.index') }}"
                       class="rounded-md border border-slate-300 px-4 py-2 text-center text-sm text-slate-700 hover:bg-slate-50">
                        Réinitialiser
                    </a>
                @endif
            </div>
            @if ($recherche !== '')
                <p class="mt-2 text-xs text-slate-500">
                    {{ $enlevements->count() }} résultat(s) pour « {{ $recherche }} ».
                </p>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Bon</th>
                        <th class="px-4 py-3">Bénéficiaire</th>
                        <th class="px-4 py-3">Téléphone</th>
                        <th class="px-4 py-3 text-center">Lignes</th>
                        <th class="px-4 py-3 text-center">État</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($enlevements as $attribution)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                ATT-{{ str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT) }}
                                <span class="block text-xs text-slate-500">
                                    Séance clôturée le {{ $attribution->session?->date_fin_session?->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->nom }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $attribution->demande?->acteur?->telephone }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $attribution->lignesAttributions->count() }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($attribution->date_destockage)
                                    <span class="rounded-full bg-green-600 px-2.5 py-1 text-xs font-semibold text-white">
                                        Servie le {{ $attribution->date_destockage->format('d/m/Y') }}
                                    </span>
                                    @if ($attribution->recipiendaire)
                                        <span class="mt-1 block text-xs text-slate-500">à {{ $attribution->recipiendaire }}</span>
                                    @endif
                                @else
                                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800">À servir</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($attribution->date_destockage)
                                    <a href="{{ route('attributions.bon', $attribution->id_attribution) }}"
                                       class="text-sm font-medium text-[--color-primary] hover:underline">Bon</a>
                                @else
                                    <a href="{{ route('enlevements.create', $attribution->id_attribution) }}"
                                       class="rounded-md bg-[--color-primary] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">
                                        Servir
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                {{ $recherche !== '' ? 'Aucun bon ne correspond à cette recherche.' : 'Aucun bon à servir.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
