@extends($layout ?? 'layouts.aderiz')

@section('content')

<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-6xl mx-auto">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[--color-primary]">
                Approvisionnements
            </h1>

            <p class="text-sm text-slate-500">
                {{ $approvisionnements->count() }} entrée(s) de stock
            </p>
        </div>

        <a href="{{ route('approvisionnements.create') }}"
           class="inline-flex items-center gap-1.5 rounded-md
                  bg-[--color-primary] px-4 py-2 text-sm font-semibold
                  text-white hover:opacity-90">

            <span class="material-symbols-outlined text-base">
                add
            </span>

            Enregistrer une entrée
        </a>
    </div>

    @include('partials.flash')

    {{-- Tableau --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">

        <table class="min-w-full divide-y divide-slate-200 text-sm">

            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3">Référence commande</th>
                    <th class="px-4 py-3">Origine</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">

                @forelse ($approvisionnements as $appro)

                    @php
                        $mouvement = $appro->mouvement;
                        $appui = $mouvement?->appui;

                        $unite = $appui?->uniteAppui?->lib_unite_appui
                            ?? 'unité';
                    @endphp

                    <tr class="hover:bg-slate-50">

                        {{-- Date --}}
                        <td class="px-4 py-3 text-slate-600">
                            {{ $appro->date_approvisionnement?->format('d/m/Y') }}
                        </td>

                        {{-- Appui --}}
                        <td class="px-4 py-3 font-medium text-slate-800">

                            @if ($appui)
                                {{ $appui->libelle }}

                                @if ($appui->typeAppui)
                                    <span class="block text-xs text-slate-500">
                                        {{ $appui->typeAppui->libelle }}
                                    </span>
                                @endif
                            @else
                                <span class="text-slate-400">
                                    Appui non associé
                                </span>
                            @endif

                        </td>

                        {{-- Quantité --}}
                        <td class="px-4 py-3 text-right font-semibold text-emerald-700">
                            @if ($mouvement)
                                +{{ $mouvement->quantite }}
                                {{ $unite }}
                            @else
                                —
                            @endif
                        </td>

                        {{-- Référence commande --}}
                        <td class="px-4 py-3 text-slate-600">
                            {{ $appro->reference_commande ?? '—' }}
                        </td>

                        {{-- Origine --}}
                        <td class="px-4 py-3 text-slate-600">
                            {{ $appro->origine ?? '—' }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5"
                            class="px-4 py-8 text-center text-slate-500">
                            Aucun approvisionnement enregistré.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>
</div>

</div>
@endsection
