@extends('layouts.impression', ['retour' => route('instructions.show', $demande->id_demande)])

@section('sur-titre', 'Document de terrain')
@section('titre', "Fiche d'instruction")
@section('sous-titre', $service->lib_service)

@section('document')
    @include('instructions.partials.fiche')
@endsection
