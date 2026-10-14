{{--
    Gabarit des écrans hors session : connexion, inscription, réinitialisation
    de mot de passe. Deux colonnes sur grand écran (un panneau institutionnel
    à gauche, le formulaire à droite) et une seule colonne sur mobile.
--}}
<!DOCTYPE html>
<html lang="fr" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ORYZA') }}</title>

        @include('layouts.partials.marque')
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-white font-sans text-slate-800 antialiased">
        <div class="flex min-h-full">

            {{-- ─────────── Panneau institutionnel (grand écran) ─────────── --}}
            <aside class="relative hidden w-1/2 overflow-hidden bg-[--color-primary] lg:flex lg:flex-col lg:justify-between">
                {{-- Halos décoratifs : de la profondeur sans image à charger. --}}
                <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-emerald-400/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-lime-300/10 blur-3xl"></div>

                <div class="relative p-12">
                    <a href="{{ route('vitrine') }}" class="inline-block" aria-label="Accueil ORYZA">
                        <img src="/images/brand/ORYZA_logo_negatif.svg" alt="ORYZA, gestion des appuis de la filière riz" class="h-16 w-auto">
                    </a>
                </div>

                <div class="relative px-12">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-white">
                        Plateforme de<br>
                        <span class="text-lime-300">gestion des appuis</span>
                    </h1>
                    <p class="mt-5 max-w-md leading-relaxed text-emerald-50/90">
                        Du dépôt de la demande à la remise des appuis au magasin : instruction par
                        les services, arbitrage en commission et suivi des stocks, au même endroit.
                    </p>

                    <ul class="mt-10 space-y-4">
                        @foreach ([
                            ['description', 'Enregistrer et suivre les demandes'],
                            ['fact_check', 'Instruire les dossiers par service'],
                            ['gavel', 'Arbitrer en commission'],
                            ['inventory', 'Servir les bons et tenir les stocks'],
                        ] as [$icone, $libelle])
                            <li class="flex items-center gap-3 text-sm text-emerald-50">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-inset ring-white/15">
                                    <span class="material-symbols-outlined text-base text-lime-300">{{ $icone }}</span>
                                </span>
                                {{ $libelle }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative p-12 text-xs text-emerald-100/70">
                    &copy; {{ now()->year }} ADERIZ, Agence pour le Développement de la Filière Riz
                </p>
            </aside>

            {{-- ────────────────────── Formulaire ────────────────────── --}}
            <main class="flex w-full flex-col lg:w-1/2">

                {{-- En-tête compact, visible seulement sur mobile --}}
                <div class="flex items-center gap-3 border-b border-slate-200 px-6 py-4 lg:hidden">
                    <a href="{{ route('vitrine') }}" aria-label="Accueil ORYZA">
                        <img src="/images/brand/ORYZA_logo_sans_slogan_couleur.svg" alt="ORYZA" class="h-9 w-auto">
                    </a>
                </div>

                <div class="flex flex-1 items-center justify-center px-6 py-12">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>

                <p class="px-6 pb-6 text-center text-xs text-slate-400 lg:hidden">
                    &copy; {{ now()->year }} ADERIZ, tous droits réservés
                </p>
            </main>
        </div>
    </body>
</html>
