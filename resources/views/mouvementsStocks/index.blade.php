@extends('layouts.aderiz')

@section('content')
<div class="p-6 bg-white rounded-xl shadow-sm space-y-6">
    <h1 class="text-xl font-bold text-slate-800">Mouvements de stock</h1>

    {{-- Formulaire de filtre --}}
    <form method="GET" action="{{ route('mouvements-stocks.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher appui, agent..." class="border-slate-300 rounded-md text-sm">
        
        <select name="id_type_mouvement" class="border-slate-300 rounded-md text-sm">
            <option value="">Tous les types</option>
            @foreach($typesMouvements as $type)
                <option value="{{ $type->id_type_mouvement_stock }}" @selected(request('id_type_mouvement') == $type->id_type_mouvement_stock)>
                    {{ $type->libelle }}
                </option>
            @endforeach
        </select>

        <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="border-slate-300 rounded-md text-sm">
        <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="border-slate-300 rounded-md text-sm">

        <div class="sm:col-span-4 flex justify-end gap-2">
            <a href="{{ route('mouvements-stocks.index') }}" class="px-3 py-1.5 text-xs border rounded-md">Réinitialiser</a>
            <button type="submit" class="px-4 py-1.5 text-xs bg-emerald-700 text-white rounded-md">Filtrer</button>
        </div>
    </form>

    {{-- Tableau des mouvements --}}
    <table class="w-full text-left text-sm border-collapse">
        <thead>
            <tr class="border-b bg-slate-50 text-xs text-slate-500 uppercase">
                <th class="p-3">Date</th>
                <th class="p-3">Type</th>
                <th class="p-3">Appui / Équipement</th>
                <th class="p-3 text-right">Quantité</th>
                <th class="p-3">Agent</th>
                <th class="p-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($mouvements as $mouvement)
                <tr>
                    <td class="p-3">{{ $mouvement->date_mouvement ?? '-' }}</td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100">
                            {{ $mouvement->typeMouvement?->lib_type_mouvement ?? 'Mouvement' }}
                        </span>
                    </td>
                    <td class="p-3 font-medium text-slate-800">
                        {{ $mouvement->appui?->libelle ?? $mouvement->equipement?->libelle ?? '-' }}
                    </td>
                    <td class="p-3 text-right font-bold">
                        {{ $mouvement->quantite }}
                    </td>
                    <td class="p-3">{{ $mouvement->agent?->nom }} {{ $mouvement->agent?->prenom }}</td>
                    <td class="p-3 text-center">
                        <a href="{{ route('mouvements-stocks.show', $mouvement->id_mouvement_stock) }}" class="text-emerald-700 hover:underline text-xs">Détails</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-slate-500">Aucun mouvement trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $mouvements->links() }}
</div>
@endsection