{{--
    Le corps de la fiche d'instruction, isolé pour servir aussi bien à la fiche
    d'un seul dossier qu'à la liasse que l'agent emporte sur le terrain.

    Attend : $demande, $service, $lignesDuService, $rapport, $autresServices.
--}}
{{-- Identification du dossier --}}
<section class="mt-6">
    <div class="flex items-start justify-between gap-6">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Dossier</p>
            <p class="text-lg font-bold text-slate-800">
                DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
            </p>
        </div>
        <div class="text-right text-sm">
            <p class="text-slate-600">Déposé le {{ $demande->date_demande?->format('d/m/Y') }}</p>
            <p class="text-slate-600">
                Mise en place souhaitée : {{ $demande->debut_periode_mise_en_place?->format('d/m/Y') }}
            </p>
        </div>
    </div>
</section>

{{-- Le demandeur et sa parcelle, en un coup d'œil --}}
<section class="mt-6 grid grid-cols-2 gap-6 text-sm">
    <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">Demandeur</p>
        <p class="mt-0.5 font-semibold text-slate-800">{{ $demande->acteur?->nom }}</p>
        <p class="text-slate-600">{{ $demande->typeActeur?->lib_type_acteur }}</p>
        <p class="text-slate-600">{{ $demande->acteur?->telephone }}</p>
        @if ($demande->acteur?->email)
            <p class="text-slate-600">{{ $demande->acteur->email }}</p>
        @endif
    </div>
    <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">Localisation</p>
        <p class="mt-0.5 font-semibold text-slate-800">{{ $demande->localite?->lib_localite }}</p>
        <p class="text-slate-600">{{ $demande->localite?->departement?->lib_departement }}</p>
        <p class="mt-2 text-slate-600">
            Superficie totale :
            <span class="font-semibold text-slate-800">
                {{ number_format($demande->systemesProduction->sum('superficie'), 2, ',', ' ') }} ha
            </span>
        </p>
    </div>
</section>

{{-- Superficies déclarées --}}
<section class="mt-6 insecable">
    <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">Systèmes de production</h2>
    <table class="mt-2 w-full text-sm">
        <thead>
            <tr class="border-b border-slate-300 text-left text-xs uppercase text-slate-500">
                <th class="pb-1">Système</th>
                <th class="pb-1 text-right">Superficie déclarée (ha)</th>
                <th class="pb-1 text-right">Constat sur le terrain</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($demande->systemesProduction as $systeme)
                <tr>
                    <td class="py-1.5 text-slate-800">{{ $systeme->typeSystemeProduction?->lib_type_systeme_production }}</td>
                    <td class="py-1.5 text-right text-slate-800">{{ number_format($systeme->superficie, 2, ',', ' ') }}</td>
                    {{-- Colonne laissée vide : l'agent la remplit au stylo lors de la visite. --}}
                    <td class="py-1.5"><span class="block border-b border-dotted border-slate-400">&nbsp;</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>

{{-- Les appuis relevant du service, avec une colonne à remplir à la main --}}
<section class="mt-6 insecable">
    <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">
        Appuis relevant du {{ $service->lib_service }}
    </h2>
    <table class="mt-2 w-full text-sm">
        <thead>
            <tr class="border-b border-slate-300 text-left text-xs uppercase text-slate-500">
                <th class="pb-1">Appui</th>
                <th class="pb-1 text-right">Demandé</th>
                <th class="pb-1 text-right w-40">Quantité proposée</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($lignesDuService as $ligne)
                <tr>
                    <td class="py-1.5 text-slate-800">
                        {{ $ligne->appui?->libelle }}
                        <span class="text-xs text-slate-500">({{ $ligne->appui?->typeAppui?->libelle }})</span>
                    </td>
                    <td class="py-1.5 text-right text-slate-800">
                        {{ $ligne->quantite_demandee }}
                        {{ $ligne->appui?->typeAppui?->uniteTypeAppui?->lib_unite_type_appui }}
                    </td>
                    <td class="py-1.5"><span class="block border-b border-dotted border-slate-400">&nbsp;</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($autresServices->isNotEmpty())
        <p class="mt-2 text-xs text-slate-500">
            Le dossier sollicite également des appuis relevant de : {{ $autresServices->join(', ') }}.
        </p>
    @endif
</section>

{{-- Pièces à vérifier, à cocher au stylo --}}
<section class="mt-6 insecable">
    <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">Pièces justificatives</h2>
    @if ($demande->piecesDemandes->isEmpty())
        <p class="mt-2 text-sm text-slate-500">Aucune pièce jointe au dossier.</p>
    @else
        <ul class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
            @foreach ($demande->piecesDemandes as $piece)
                <li class="flex items-center gap-2 text-slate-700">
                    <span class="inline-block h-3.5 w-3.5 border border-slate-400"></span>
                    {{ $piece->typePiece?->lib_type_piece }}
                </li>
            @endforeach
        </ul>
    @endif
</section>

{{-- La zone d'écriture manuscrite : c'est la raison d'être de la fiche --}}
<section class="mt-6 insecable">
    <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">
        Observations de l'agent instructeur
    </h2>
    @if ($rapport?->commentaire)
        <p class="mt-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
            Saisi dans la plateforme : {{ $rapport->commentaire }}
        </p>
    @endif
    {{-- Huit lignes d'environ 7 mm : de quoi écrire à l'aise sans pousser
         la signature sur un second feuillet. --}}
    <div class="mt-2 border border-slate-300 p-3">
        @for ($ligne = 0; $ligne < 8; $ligne++)
            <div class="border-b border-dotted border-slate-300 py-2">&nbsp;</div>
        @endfor
    </div>
</section>

{{-- Décision et signature --}}
<section class="mt-6 insecable">
    <div class="flex items-center gap-8 text-sm">
        <span class="font-semibold text-slate-700">Avis du service :</span>
        <span class="flex items-center gap-2 text-slate-700">
            <span class="inline-block h-3.5 w-3.5 border border-slate-400"></span> Favorable, à porter en commission
        </span>
        <span class="flex items-center gap-2 text-slate-700">
            <span class="inline-block h-3.5 w-3.5 border border-slate-400"></span> Défavorable
        </span>
    </div>

    <div class="mt-10 grid grid-cols-2 gap-10 text-xs text-slate-500">
        <p class="border-t border-slate-400 pt-1">Date et signature de l'agent instructeur</p>
        <p class="border-t border-slate-400 pt-1">Visa du chef de service</p>
    </div>
</section>
