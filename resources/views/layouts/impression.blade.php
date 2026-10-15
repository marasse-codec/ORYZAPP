{{--
    Gabarit des documents destinés au papier : fiche d'instruction, synthèse de
    commission, bon d'attribution, pièces jointes. À l'écran, la feuille est
    montrée à ses proportions réelles sous une barre d'actions ; à l'impression,
    seul le document subsiste.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titre', 'Document') | ADERIZ</title>
    @include('layouts.partials.marque')
    @vite(['resources/css/app.css'])
    <style>
        /* ------------------------------------------------------------- Écran */

        /* La feuille est montrée à la taille qu'elle aura en sortant de
           l'imprimante : ce qui déborde à l'écran débordera sur le papier. */
        .feuille {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 14mm;
        }

        @media (max-width: 900px) {
            .feuille { width: 100%; min-height: 0; padding: 6mm; }
        }

        /* ------------------------------------------------------------ Papier */

        @page { size: A4; margin: 15mm 14mm; }

        @media print {
            /* Tailwind dimensionne en rem : abaisser la base recale d'un seul
               geste toute la typographie (et les espacements) sur la mesure
               d'une colonne A4. C'est ce qui fait tenir la fiche sur un feuillet. */
            html { font-size: 13px; }

            /* Sans quoi le navigateur laisse tomber aplats et couleurs de marque. */
            body {
                margin: 0;
                padding: 0;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .sans-impression { display: none !important; }

            /* La feuille, c'est désormais la page : ni cadre, ni ombre, ni marge. */
            .feuille {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }

            /* Un tableau qui déborde reprend son en-tête sur la feuille suivante. */
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }

            /* Ne pas couper ce qui se lit d'un bloc. */
            tr, img, .insecable { break-inside: avoid; }
            h1, h2, h3 { break-after: avoid; }
            p, li { orphans: 3; widows: 3; }

            /* Sur le papier un lien n'est plus qu'un mot : le souligné dessert. */
            a { color: inherit; text-decoration: none; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-200 py-6 text-slate-800 antialiased">

    {{-- La barre suit le défilement : sur un document long, le bouton
         d'impression reste à portée. --}}
    <div class="sans-impression sticky top-0 z-10 mb-6 border-b border-slate-300 bg-slate-200/90 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center justify-between gap-3 px-4">
            <a href="{{ $retour ?? url()->previous() }}"
               class="text-sm font-medium text-[--color-primary] hover:underline">← Retour</a>
            <p class="hidden text-xs uppercase tracking-[0.2em] text-slate-500 sm:block">@yield('titre')</p>
            <button onclick="window.print()"
                    class="rounded-md bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Imprimer / Enregistrer en PDF
            </button>
        </div>
    </div>

    <main class="feuille mx-auto flex flex-col bg-white shadow-lg ring-1 ring-slate-300">

        {{-- L'en-tête : le papier à lettre de l'agence, sauf pour un document
             qui impose le sien (section « entete »). --}}
        @hasSection('entete')
            @yield('entete')
        @else
        <header class="insecable">
            <div class="flex items-end justify-between gap-8">
                <div>
                    <x-ui.logo-aderiz class="!mx-0" />
                    <p class="mt-1 text-[11px] leading-tight text-slate-500">
                        Agence pour le Développement de la Filière Riz<br>
                        Plateforme ORYZA de gestion des appuis
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-400">
                        @yield('sur-titre', 'Document')
                    </p>
                    <p class="mt-0.5 text-lg font-bold uppercase leading-tight tracking-wide text-slate-900">
                        @yield('titre')
                    </p>
                    <p class="text-xs font-medium text-slate-500">@yield('sous-titre')</p>
                </div>
            </div>
            {{-- Double filet : le trait plein porte la couleur de l'agence. --}}
            <div class="mt-3 h-[3px] bg-[--color-primary]"></div>
            <div class="mt-px h-px bg-slate-300"></div>
        </header>
        @endif

        <div class="flex-1">
            @yield('document')
        </div>

        <footer class="insecable mt-10 flex items-center justify-between gap-4 border-t border-slate-300 pt-2 text-[10px] text-slate-400">
            <span class="flex items-center gap-1.5"><img src="/images/brand/ORYZA_embleme_couleur.svg" alt="" class="h-3.5 w-3.5"> ORYZA, ADERIZ : @yield('titre')@hasSection('sous-titre'), @yield('sous-titre')@endif</span>
            <span>Édité le {{ now()->format('d/m/Y à H:i') }}</span>
        </footer>
    </main>

</body>
</html>
