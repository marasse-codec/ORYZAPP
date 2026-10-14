{{--
    Les pièces du dossier mises en page pour le papier : un bordereau, puis
    chaque image reproduite sur sa propre feuille. Les PDF, que le navigateur
    n'intègre pas au flux d'impression, restent au bordereau et se tirent
    depuis leur propre fenêtre.
--}}
@extends('layouts.impression', ['retour' => route('demandes.show', $demande->id_demande)])

@php
    $reference = 'DEM-'.$demande->date_demande?->format('Y').'-'.str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT);
    $pieces = $demande->piecesDemandes;
    $nomDemandeur = $demande->nom_structure ?: ($demande->acteur?->nom ?? 'Demandeur');
    $departementsStr = $demande->localites->pluck('departement.lib_departement')->filter()->unique()->join(', ');
    $localitesStr = $demande->localites->pluck('lib_localite')->filter()->unique()->join(', ');
@endphp

@section('sur-titre', 'Annexes au dossier')
@section('titre', 'Pièces justificatives')
@section('sous-titre', $reference)

@push('styles')
    <style>
        .piece-image { max-width: 100%; height: auto; }

        @media print {
            /* Une pièce par feuille : le dossier papier se reconstitue tel quel. */
            .piece { break-before: page; break-inside: avoid; margin-top: 0; }
            /* L'image occupe la hauteur utile d'une A4, sans être déformée. */
            .piece-image { max-height: 23cm; }
        }
    </style>
@endpush

@section('document')

    <section class="mt-6 flex items-start justify-between gap-6 text-sm">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Demandeur</p>
            <p class="mt-0.5 font-semibold text-slate-800">{{ $nomDemandeur }}</p>
            @if ($demande->nom_structure && $demande->acteur?->nom)
                <p class="text-xs text-slate-500">Rep : {{ $demande->acteur->nom }}</p>
            @endif
            <p class="text-slate-600">
                {{ $localitesStr ?: 'Localité non spécifiée' }}
                @if ($departementsStr)
                    ({{ $departementsStr }})
                @endif
            </p>
        </div>
        <div class="text-right text-slate-600">
            <p>Dossier déposé le {{ $demande->date_demande?->format('d/m/Y') }}</p>
            <p>{{ $pieces->count() }} pièce{{ $pieces->count() > 1 ? 's' : '' }} au dossier</p>
        </div>
    </section>

    @if ($pieces->isEmpty())
        <p class="mt-8 text-sm text-slate-500">Aucune pièce n'est jointe à ce dossier.</p>
    @else

        {{-- Le bordereau : ce que contient le dossier, et sous quelle forme. --}}
        <section class="mt-6 insecable">
            <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">Bordereau des pièces</h2>
            <table class="mt-2 w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-300 text-left text-xs uppercase text-slate-500">
                        <th class="w-8 pb-1">N°</th>
                        <th class="pb-1">Pièce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pieces as $rang => $piece)
                        <tr>
                            <td class="py-1.5 text-slate-500">{{ $rang + 1 }}</td>
                            <td class="py-1.5 text-slate-800">{{ $piece->typePiece?->lib_type_piece }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($pieces->contains(fn ($piece) => ! $piece->estImage()))
                <p class="mt-3 text-xs text-slate-500">
                    Les pièces au format PDF ne sont pas reproduites dans ce document : le navigateur ne les
                    intègre pas au flux d'impression. Elles se tirent depuis leur propre fenêtre, par le
                    bouton « Ouvrir et imprimer ce PDF ».
                </p>
            @endif
        </section>

        {{-- Chaque pièce, reproduite en pleine page. --}}
        @foreach ($pieces as $rang => $piece)
            <section class="{{ $piece->estImage() ? 'piece' : 'sans-impression' }} mt-8">
                <div class="flex items-baseline justify-between gap-4 border-b border-slate-200 pb-1">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-[--color-primary]">
                        Pièce n°{{ $rang + 1 }} : {{ $piece->typePiece?->lib_type_piece }}
                    </h2>
                    <span class="text-xs text-slate-500">{{ $reference }}, {{ $piece->nomFichier() }}</span>
                </div>

                @if ($piece->estImage())
                    <img src="{{ $piece->url() }}"
                         alt="{{ $piece->typePiece?->lib_type_piece }}"
                         class="piece-image mx-auto mt-4 block">
                @elseif ($piece->estPdf())
                    <object data="{{ $piece->url() }}" type="application/pdf"
                            class="mt-4 h-[28rem] w-full rounded border border-slate-200">
                        <p class="p-4 text-sm text-slate-600">Aperçu indisponible dans ce navigateur.</p>
                    </object>
                    <p class="mt-3 text-center">
                        <a href="{{ $piece->url() }}" target="_blank" rel="noopener"
                           class="inline-block rounded-md bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                            Ouvrir et imprimer ce PDF
                        </a>
                    </p>
                @else
                    <p class="mt-4 text-center">
                        <a href="{{ $piece->url() }}" target="_blank" rel="noopener"
                           class="text-sm font-medium text-[--color-primary] hover:underline">
                            Ouvrir le fichier
                        </a>
                    </p>
                @endif
            </section>
        @endforeach

    @endif
@endsection