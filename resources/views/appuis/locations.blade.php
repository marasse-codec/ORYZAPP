<!-- Cette page affiche les appuis de la base de données -->
@extends('layouts.aderiz')

@section('content')

<div class="grid grid-cols-3">

@foreach($locations as $location)
    <div class="bg-white shadow-md rounded p-4 m-4">
        <h2 class="text-lg font-semibold mb-2">{{ $location->ligneAttribution->appui->libelle }}</h2>
            <p><strong>Prix:</strong> {{ $location->ligneAttribution->cout_unitaire }}</p>
            <p><strong>Locataire:</strong> {{ $location->ligneAttribution->attribution->demande->acteur->nom }}</p>
            <p><strong>Date debut location:</strong> {{ $location->date_debut_location }}</p>
            <p><strong>Date fin location:</strong> {{ $location->date_fin_location }}</p>
    </div>
@endforeach

</div>

@endsection()