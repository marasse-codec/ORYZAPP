<!-- Cette page affiche les reponses de la base de données -->
@extends('layouts.aderiz')

@section('content')

<div class="grid grid-cols-3">

@foreach($reponses as $reponse)
    <a href="">
        <div class="bg-white shadow-md rounded p-4 m-4 transition duration-150 hover:bg-blue-200 hover:shadow-xl">
            <h2 class="text-lg font-semibold mb-2">Reponse {{ $reponse->id_reponse }}</h2>
            <strong><span class="h3">Appuis : </span></strong>
            @foreach($reponse->ligneReponses as $ligneReponse)
                <p> - {{ $ligneReponse->appui->libelle }}</p>
            @endforeach
            <p><strong>Date:</strong> {{ $reponse->date_reponse }}</p>
        </div>
    </a>
@endforeach

</div>

@endsection()