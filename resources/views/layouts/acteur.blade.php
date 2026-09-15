<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADERIZ | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 h-screen lg:flex">

    <!-- SIDEBAR (Navigation) -->
    <aside class="lg:w-64 bg-[--color-primary] text-white flex flex-col">
        <div class="p-6 text-sm font-bold border-b border-slate-700">ADERIZ GESTION APPUIS</div>

        <!-- Bloc parent du menu -->
        <div x-data="{ open: false }" class="relative">
            
            <!-- Bouton déclencheur -->
            <button @click="open = !open" 
                    class="flex items-center justify-between w-full p-3 text-[--color-secondary] font-[--text-body] hover:bg-slate-700 rounded">
                <span>Gestion Demandes</span>
                <!-- Icône de flèche (rotation si ouvert) -->
                <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Liste déroulante -->
            <div x-show="open"
                x-cloak 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                class="pl-4 mt-2 space-y-1 bg-slate-900 rounded">
                
                <a href="{{route('demande.index')}}" class="block p-2 text-sm text-gray-300 hover:text-white">Voir demandes</a>

                <a href="{{route('demande.create')}}" class="block p-2 text-sm text-gray-300 hover:text-white">Nouvelle demande producteur</a>
            </div>
        </div>

        <div class="relative bottom-0 w-full p-4 border-t border-slate-700">
            <form id="logout-form" action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-red-500 hover:underline">
                    Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <!-- CONTENT AREA -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- HEADER -->
        <header class="flex h-16 bg-white border-b flex items-center justify-between px-8">
            <span class="font-semibold text-gray-700">Bienvenue, Acteur</span>
            <div class="text-sm text-gray-500">ADERIZ - Système de Distribution</div>
        </header>

        <!-- CONTENU DYNAMIQUE -->
        <main class="flex-1 bg-orange-200 overflow-y-auto p-8">
            <!-- C'est ici que votre formulaire de demande s'injectera -->
            @yield('content')
        </main>
    </div>

</body>
</html>