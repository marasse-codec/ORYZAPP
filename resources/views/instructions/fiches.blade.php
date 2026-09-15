{{--
    La liasse : une fiche par dossier retenu, chacune sur sa feuille. L'agent
    qui part en tournée sort ses dossiers d'un seul tirage plutôt qu'un à un.
--}}
@extends('layouts.impression', ['retour' => route('instructions.index')])

@section('sur-titre', 'Liasse de terrain')
@section('titre', "Fiches d'instruction")
@section('sous-titre', $service->lib_service.' — '.$fiches->count().' dossier'.($fiches->count() > 1 ? 's' : ''))

@push('styles')
    <style>
        /* À l'écran, un filet marque ce que le papier séparera par une feuille. */
        .fiche + .fiche {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px dashed #cbd5e1;
        }

        @media print {
            .fiche + .fiche {
                break-before: page;
                margin-top: 0;
                padding-top: 0;
                border-top: 0;
            }
        }
    </style>
@endpush

@section('document')
    @foreach ($fiches as $fiche)
        <div class="fiche">
            @include('instructions.partials.fiche', $fiche)
        </div>
    @endforeach
@endsection
