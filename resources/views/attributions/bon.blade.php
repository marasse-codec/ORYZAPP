{{--
    Le bon remis au bénéficiaire : il vaut titre de retrait au magasin, d'où
    les mentions de retrait et les deux émargements en pied.
--}}
@extends('layouts.impression', ['retour' => route('demandes.show', $attribution->id_demande)])

@section('sur-titre', 'Pièce à conserver')
@section('titre', "Bon d'attribution")
@section('sous-titre', 'ATT-'.str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT))

@section('document')

    <section class="mt-8 grid grid-cols-2 gap-8 text-sm">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Bénéficiaire</p>
            <p class="mt-0.5 font-semibold text-slate-800">{{ $attribution->demande?->acteur?->nom }}</p>
            <p class="text-slate-600">{{ $attribution->demande?->typeActeur?->lib_type_acteur }}</p>
            <p class="text-slate-600">{{ $attribution->demande?->acteur?->telephone }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Dossier</p>
            <p class="mt-0.5 font-semibold text-slate-800">
                DEM-{{ $attribution->demande?->date_demande?->format('Y') }}-{{ str_pad($attribution->id_demande, 3, '0', STR_PAD_LEFT) }}
            </p>
            <p class="text-slate-600">
                {{-- Affiche toutes les localités séparées par une virgule --}}
                {{ $attribution->demande?->localites->pluck('lib_localite')->implode(', ') ?: '—' }}

                @if ($attribution->demande?->localites->isNotEmpty())
                    ({{ $attribution->demande->localites->pluck('departement.lib_departement')->unique()->filter()->implode(', ') }})
                @endif
            </p>
            <p class="text-slate-600">
                Séance du {{ $attribution->session?->date_debut_session?->format('d/m/Y') }}
            </p>
        </div>
    </section>

    <section class="mt-8">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-300 text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="pb-2">Appui</th>
                    <th class="pb-2 text-right">Quantité</th>
                    <th class="pb-2 text-right">Coût unitaire</th>
                    <th class="pb-2 text-right">Remise</th>
                    <th class="pb-2 text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($attribution->lignesAttributions as $ligne)
                    <tr>
                        <td class="py-2 text-slate-800">{{ $ligne->appui?->libelle }}</td>
                        <td class="py-2 text-right text-slate-800">
                            {{ $ligne->quantite_attribuee }}
                            {{ $ligne->appui?->typeAppui?->uniteTypeAppui?->lib_unite_type_appui }}
                        </td>
                        <td class="py-2 text-right text-slate-600">
                            {{ number_format((float) $ligne->cout_attribution, 0, ',', ' ') }} F
                        </td>
                        <td class="py-2 text-right text-slate-600">{{ rtrim(rtrim(number_format((float) $ligne->remise, 2, ',', ' '), '0'), ',') }} %</td>
                        <td class="py-2 text-right font-medium text-slate-800">
                            {{ number_format($ligne->montant(), 0, ',', ' ') }} F
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-300 font-bold">
                    <td colspan="4" class="pt-2 text-slate-700">TOTAL À RÉGLER</td>
                    <td class="pt-2 text-right text-[--color-primary]">
                        {{ number_format($attribution->montantTotal(), 0, ',', ' ') }} F CFA
                    </td>
                </tr>
            </tfoot>
        </table>
    </section>

    @if ($attribution->commentaires)
        <section class="mt-6 insecable border-l-4 border-slate-300 pl-4 text-sm text-slate-600">
            <p class="text-xs uppercase tracking-wide text-slate-500">Observations de la commission</p>
            <p class="mt-1">{{ $attribution->commentaires }}</p>
        </section>
    @endif

    <section class="mt-8 insecable rounded border border-slate-300 bg-slate-50 p-4 text-sm">
        @if ($attribution->date_destockage)
            <p class="font-semibold text-slate-800">
                Appuis servis le {{ $attribution->date_destockage->format('d/m/Y') }}.
            </p>
        @else
            <p class="text-slate-600">
                <span class="font-semibold text-slate-800">Retrait :</span>
                centre de conditionnement de Yamoussoukro, sur présentation de ce bon et d'une pièce
                d'identité. Le retrait par un tiers exige en outre une procuration.
            </p>
        @endif
    </section>

    <section class="mt-12 insecable grid grid-cols-2 gap-12 text-xs text-slate-500">
        <p class="border-t border-slate-400 pt-1">Le bénéficiaire</p>
        <p class="border-t border-slate-400 pt-1">Pour l'ADERIZ</p>
    </section>

</section>

@endsection
