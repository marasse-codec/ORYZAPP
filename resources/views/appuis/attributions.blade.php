@extends('layouts.aderiz')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Gestion des attributions et retraits</h2>

    <form method="POST" action="{{ route('attributions.store') }}" class="mb-8">
        @csrf
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Demande</label>
                <select name="id_demande" class="w-full border rounded p-2">
                    @foreach ($demandes as $demande)
                        <option value="{{ $demande->id_demande }}">{{ $demande->id_demande }} - {{ $demande->acteur->nom ?? 'Acteur' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Utilisateur</label>
                <select name="id_user" class="w-full border rounded p-2">
                    @foreach (\App\Models\User::all() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Appui</label>
                <select name="id_appui" class="w-full border rounded p-2">
                    @foreach ($appuis as $appui)
                        <option value="{{ $appui->id_appui }}">{{ $appui->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Quantité</label>
                <input type="number" name="quantite" value="1" class="w-full border rounded p-2" min="1">
            </div>
            <div>
                <label class="block text-sm font-medium">Coût d'attribution</label>
                <input type="number" step="0.01" name="cout_attribution" value="0" class="w-full border rounded p-2">
            </div>
        </div>
        <button class="mt-4 bg-green-600 text-white px-4 py-2 rounded">Créer l'attribution</button>
    </form>

    <form method="POST" action="{{ route('retraits.store') }}">
        @csrf
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium">Attribution</label>
                <select name="id_attribution" class="w-full border rounded p-2">
                    @foreach (\App\Models\Attribution::all() as $attribution)
                        <option value="{{ $attribution->id_attribution }}">#{{ $attribution->id_attribution }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Date de retrait</label>
                <input type="date" name="date_retrait" class="w-full border rounded p-2" value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-sm font-medium">CNI</label>
                <input type="text" name="cni" class="w-full border rounded p-2">
            </div>
        </div>
        <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Enregistrer le retrait</button>
    </form>
</div>
@endsection
