@php
    use App\Enums\PermissionEnum;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    @livewireStyles
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADERIZ Systeme de Gestion des appuis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 h-screen lg:flex">

    <aside class="lg:w-64 bg-[--color-primary] text-white flex flex-col">
        <div class="p-6 text-sm font-bold border-b border-slate-700">ADERIZ GESTION APPUIS</div>

        <a href="{{ route('dashboard') }}"
           class="flex items-center border-b border-slate-700 w-full p-3 text-white hover:bg-slate-700">
            Tableau de bord
        </a>

        {{-- Le menu suit les permissions du rôle : chaque bloc disparaît pour
             qui n'a pas à s'en servir. --}}
        <x-menu-groupe titre="Demandes">
            @can(PermissionEnum::creerDemande->value)
                <a href="{{ route('demandes.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Nouvelle demande</a>
            @endcan
            <a href="{{ route('demandes.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les demandes</a>
        </x-menu-groupe>

        @can(PermissionEnum::instruireDemande->value)
            <x-menu-groupe titre="Instruction">
                <a href="{{ route('instructions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Demandes à instruire</a>
            </x-menu-groupe>
        @endcan

        @can(PermissionEnum::gererCommission->value)
            <x-menu-groupe titre="Commissions">
                <a href="{{ route('commissions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les sessions</a>
                <a href="{{ route('commissions.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Ouvrir une session</a>
                <a href="{{ route('commissions.synthese') }}" target="_blank" class="block p-2 text-sm text-gray-300 hover:text-white">Synthèse à imprimer</a>
            </x-menu-groupe>
        @endcan

            <x-menu-groupe titre="Attributions">
                <a href="{{ route('attributions.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir les attributions</a>
                @can(PermissionEnum::attribuerAppui->value)
                <a href="{{ route('attributions.simulation') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Simuler les sorties</a>
                @endcan
            </x-menu-groupe>

        @can(PermissionEnum::gererStock->value)
            <x-menu-groupe titre="Stocks">
                <a href="{{ route('appuis.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Catalogue des appuis</a>
                <a href="{{ route('appuis.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Nouvel appui</a>
                <a href="{{ route('approvisionnements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Approvisionnements</a>
                <a href="{{ route('recuperations.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Sorties de stock</a>
                <a href="{{ route('equipements.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Gestion des Équipements</a>
            </x-menu-groupe>
        @endcan

        {{-- Ressources --}}

            <x-menu-groupe titre="Équipement">
                <a href="{{ route('equipements.retour.create') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Retour d'équipement</a>
            </x-menu-groupe>
            <x-menu-groupe titre="Ressources">
                <a href="{{ route('mouvements-stocks.index') }}" class="block p-2 text-sm text-gray-300 hover:text-white">Mouvements</a>
            </x-menu-groupe>
        <div class="mt-auto w-full p-4 border-t border-slate-700">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-red-400 text-sm hover:underline">Se déconnecter</button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex h-16 bg-white border-b items-center justify-between px-8">
            <span class="font-semibold text-gray-700">Bienvenue {{ auth()->user()->name }}</span>
            <div class="text-sm text-gray-500">Système de gestion des appuis</div>
        </header>

        <main class="flex-1 bg-[--color-second] overflow-y-auto">
            @yield('content')
        </main>
    </div>
    @livewireScripts
</body>
</html>
