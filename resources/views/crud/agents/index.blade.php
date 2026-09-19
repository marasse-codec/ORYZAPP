@extends('layouts.aderiz')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestion des Appuis</h1>
            <p class="text-sm text-gray-500">Catalogue et gestion CRUD des appuis ADERIZ</p>
        </div>
    </div>

    {{-- Intégration du composant Livewire Table Filament --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        @livewire('agent-table')
    </div>
</div>
@endsection