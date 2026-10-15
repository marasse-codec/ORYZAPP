{{--
    Page publique destinée aux acteurs de la filière. Elle explique la démarche
    et présente le catalogue, sans jamais exposer l'état des stocks, qui ne
    regarde que la commission et le magasin. Une icône discrète dans la
    navigation mène les agents ADERIZ à la connexion (/login).
--}}
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORYZA, appuis à la filière riz (ADERIZ)</title>
    <meta name="description" content="Comment solliciter un appui auprès de l'ADERIZ : la démarche, les pièces à fournir et le catalogue des appuis disponibles pour les acteurs de la filière riz.">
    @include('layouts.partials.marque')
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 antialiased">

{{-- ══════════════════════════════ Navigation ══════════════════════════════ --}}
<header x-data="{ ouvert: false }"
        class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-md">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="#" aria-label="Accueil ORYZA">
            <img src="/images/brand/ORYZA_logo_couleur.svg" alt="ORYZA, gestion des appuis de la filière riz" class="h-11 w-auto">
        </a>

        <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
            <a href="#demarche" class="transition hover:text-[--color-primary]">La démarche</a>
            <a href="#pieces" class="transition hover:text-[--color-primary]">Pièces à fournir</a>
            <a href="#appuis" class="transition hover:text-[--color-primary]">Catalogue</a>
            <a href="#contact" class="transition hover:text-[--color-primary]">Nous joindre</a>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" title="Espace agents ADERIZ" aria-label="Connexion à l'espace agents ADERIZ"
               class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 hover:text-[--color-primary]">
                <span class="material-symbols-outlined">account_circle</span>
            </a>
            <button @click="ouvert = !ouvert" class="text-slate-600 md:hidden" aria-label="Menu">
                <span class="material-symbols-outlined" x-text="ouvert ? 'close' : 'menu'">menu</span>
            </button>
        </div>
    </nav>

    <div x-show="ouvert" x-cloak x-transition class="border-t border-slate-200 md:hidden">
        <div class="space-y-1 px-6 py-3 text-sm font-medium text-slate-600">
            <a href="#demarche" @click="ouvert = false" class="block py-2">La démarche</a>
            <a href="#pieces" @click="ouvert = false" class="block py-2">Pièces à fournir</a>
            <a href="#appuis" @click="ouvert = false" class="block py-2">Catalogue</a>
            <a href="#contact" @click="ouvert = false" class="block py-2">Nous joindre</a>
        </div>
    </div>
</header>

{{-- ════════════════════════════════ Accroche ═══════════════════════════════ --}}
<section class="relative overflow-hidden bg-[--color-primary]">
    {{-- Halos décoratifs : de la profondeur sans image à charger. --}}
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-emerald-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-lime-300/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-6 py-20 sm:py-28">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-emerald-50 ring-1 ring-inset ring-white/20">
                <span class="h-1.5 w-1.5 rounded-full bg-lime-300"></span>
                Campagne {{ now()->year }}-{{ now()->year + 1 }}
            </span>

            <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                Des appuis concrets pour<br class="hidden sm:block">
                <span class="text-lime-300">votre exploitation rizicole</span>
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-emerald-50/90">
                Semences, intrants et équipements agricoles à disposition des producteurs,
                groupements, coopératives et entreprises de la filière. Voici comment en
                bénéficier, et ce que vous pouvez demander.
            </p>

            <div class="mt-9 flex flex-wrap gap-3">
                <a href="#demarche"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-[--color-primary] shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:shadow-xl">
                    <span class="material-symbols-outlined text-lg">route</span>
                    Comment faire une demande
                </a>
                <a href="#appuis"
                   class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold text-white ring-1 ring-inset ring-white/30 transition hover:bg-white/10">
                    <span class="material-symbols-outlined text-lg">inventory_2</span>
                    Voir le catalogue
                </a>
            </div>
        </div>

        {{-- Repères chiffrés, tirés du référentiel réel --}}
        <dl class="mt-16 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/15 sm:grid-cols-3">
            @foreach ([
                ['category', $nbTypesAppuis, "types d'appuis"],
                ['groups', $typesActeurs->count(), "profils d'acteurs éligibles"],
                ['agriculture', $typesSystemesProduction->count(), 'systèmes de production'],
            ] as [$icone, $valeur, $libelle])
                <div class="bg-[--color-primary] p-5">
                    <span class="material-symbols-outlined text-lime-300">{{ $icone }}</span>
                    <dt class="mt-2 text-3xl font-bold text-white">{{ $valeur }}</dt>
                    <dd class="text-xs text-emerald-100/80">{{ $libelle }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- ════════════════════════════════ Démarche ═══════════════════════════════ --}}
<section id="demarche" class="mx-auto max-w-6xl scroll-mt-20 px-6 py-20 sm:py-24">
    <div class="max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-widest text-or-600">La démarche</p>
        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Quatre étapes, du dépôt au retrait
        </h2>
        <p class="mt-4 text-lg leading-relaxed text-slate-600">
            Votre demande se dépose auprès de votre délégué de pôle, qui la saisit dans la
            plateforme. Elle suit ensuite un parcours balisé, dont vous êtes informé à chaque étape.
        </p>
    </div>

    <ol class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['description', 'Vous déposez votre dossier', "Vous indiquez votre localité, vos superficies par système de production et les appuis souhaités avec leurs quantités, pièces justificatives à l'appui."],
            ['fact_check', 'Les services instruisent', "Chaque service fournisseur examine la part de la demande qui le concerne, se déplace si nécessaire, et rend un avis motivé."],
            ['gavel', 'La commission arbitre', "Une fois tous les avis favorables réunis, la commission décide des quantités accordées, ou rejette le dossier en motivant sa décision."],
            ['local_shipping', 'Vous retirez vos appuis', "Un bon d'attribution vous est remis. Vous retirez les appuis au centre de conditionnement sur présentation du bon et de votre pièce d'identité."],
        ] as $index => [$icone, $titre, $texte])
            <li class="group relative rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:border-[--color-primary]/30 hover:shadow-lg hover:shadow-slate-200/60">
                <div class="flex items-center justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-[--color-primary] transition group-hover:bg-[--color-primary] group-hover:text-white">
                        <span class="material-symbols-outlined">{{ $icone }}</span>
                    </span>
                    <span class="text-4xl font-bold text-slate-100 transition group-hover:text-emerald-100">
                        {{ $index + 1 }}
                    </span>
                </div>
                <h3 class="mt-5 font-semibold text-slate-900">{{ $titre }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $texte }}</p>
            </li>
        @endforeach
    </ol>

    <div class="mt-10 flex items-start gap-3 rounded-xl border-l-4 border-or-400 bg-amber-50 p-5">
        <span class="material-symbols-outlined text-or-600">info</span>
        <p class="text-sm leading-relaxed text-amber-900">
            <span class="font-semibold">Retrait par un tiers.</span>
            Si vous ne pouvez pas vous déplacer, la personne mandatée devra présenter sa propre
            pièce d'identité <span class="font-semibold">et</span> une procuration signée de votre main.
        </p>
    </div>
</section>

{{-- ══════════════════════════════ Pièces requises ══════════════════════════ --}}
<section id="pieces" class="scroll-mt-20 border-y border-slate-200 bg-slate-50 py-20 sm:py-24">
    <div class="mx-auto max-w-6xl px-6">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-or-600">Préparer son dossier</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                Les pièces à fournir
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                Elles dépendent de votre forme juridique. Rassemblez-les avant de vous présenter :
                un dossier incomplet ne peut pas être enregistré.
            </p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($typesActeurs as $typeActeur)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-[--color-primary]/30 hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-xs font-bold text-[--color-primary]">
                            {{ $typeActeur->code_type_acteur }}
                        </span>
                        <h3 class="font-semibold text-slate-900">{{ $typeActeur->lib_type_acteur }}</h3>
                    </div>

                    @if ($typeActeur->typesPieces->isEmpty())
                        <p class="mt-4 text-sm text-slate-500">Aucune pièce particulière exigée.</p>
                    @else
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($typeActeur->typesPieces as $piece)
                                <li class="flex items-start gap-2.5 text-sm">
                                    <span class="material-symbols-outlined mt-px text-base {{ $piece->pivot->est_obligatoire ? 'text-[--color-primary]' : 'text-slate-300' }}">
                                        {{ $piece->pivot->est_obligatoire ? 'task_alt' : 'radio_button_unchecked' }}
                                    </span>
                                    <span class="{{ $piece->pivot->est_obligatoire ? 'text-slate-700' : 'text-slate-500' }}">
                                        {{ $piece->lib_type_piece }}
                                        @unless ($piece->pivot->est_obligatoire)
                                            <span class="text-xs italic text-slate-400">(facultatif)</span>
                                        @endunless
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════ Catalogue ══════════════════════════════ --}}
<section id="appuis" class="mx-auto max-w-6xl scroll-mt-20 px-6 py-20 sm:py-24">
    <div class="max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-widest text-or-600">Catalogue</p>
        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Les appuis que vous pouvez demander
        </h2>
        <p class="mt-4 text-lg leading-relaxed text-slate-600">
            Vous précisez la quantité souhaitée pour chaque appui. Les quantités effectivement
            accordées relèvent de la commission d'attribution, au regard de votre superficie
            et des disponibilités.
        </p>
    </div>

    <!-- AFFICHAGE DES APPUIS -->
    <div class="mt-12 grid gap-6 md:grid-cols-2">
        @forelse ($appuisParType as $libelleType => $appuis)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg hover:shadow-slate-200/60">
                <header class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-6 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $libelleType }}</h3>
                        <p class="text-xs text-slate-500">{{ $appuis->first()?->typeAppui?->service?->lib_service }}</p>
                    </div>
                </header>

                <ul class="divide-y divide-slate-100">
                    @foreach ($appuis as $appui)
                        <li class="flex items-center justify-between gap-4 px-6 py-3.5">
                            <span class="text-sm font-medium text-slate-800">{{ $appui->libelle }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>
        @empty
            <p class="text-sm text-slate-500">Le catalogue est en cours de mise à jour.</p>
        @endforelse
    </div>

    {{-- Systèmes de production : le demandeur doit connaître ses surfaces --}}
    <div class="mt-12 rounded-2xl border border-slate-200 bg-gradient-to-br from-emerald-50/60 to-white p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-xl">
                <h3 class="text-lg font-semibold text-slate-900">Munissez-vous de vos superficies</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Votre demande précise la surface exploitée en hectares, ventilée par système
                    de production. C'est cette surface qui détermine les quantités susceptibles
                    de vous être accordées.
                </p>
            </div>
            <ul class="flex flex-wrap gap-2">
                @foreach ($typesSystemesProduction as $type)
                    <li class="rounded-full bg-white px-4 py-2 text-sm font-medium text-[--color-primary] ring-1 ring-inset ring-[--color-primary]/20">
                        {{ $type->lib_type_systeme_production }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- ═════════════════════════════════ Contact ═══════════════════════════════ --}}
<section id="contact" class="scroll-mt-20 bg-[--color-primary]">
    <div class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-white sm:text-3xl">Une question sur votre dossier ?</h2>
                <p class="mt-3 max-w-xl leading-relaxed text-emerald-50/90">
                    Votre délégué de pôle est votre interlocuteur : c'est lui qui dépose votre
                    demande, vous informe de la décision de la commission.
                </p>
            </div>

            <div class="space-y-4">
                <div class="flex items-start gap-3 rounded-xl bg-white/10 p-4 ring-1 ring-inset ring-white/15">
                    <span class="material-symbols-outlined text-lime-300">location_on</span>
                    <div class="text-sm">
                        <p class="font-semibold text-white">Centre de conditionnement</p>
                        <p class="text-emerald-50/80">Yamoussoukro</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-xl bg-white/10 p-4 ring-1 ring-inset ring-white/15">
                    <span class="material-symbols-outlined text-lime-300">badge</span>
                    <div class="text-sm">
                        <p class="font-semibold text-white">Au retrait</p>
                        <p class="text-emerald-50/80">Bon d'attribution + pièce d'identité</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-slate-500 sm:flex-row">
        <div class="flex items-center gap-3">
            <img src="/images/brand/ORYZA_embleme_couleur.svg" alt="" class="h-7 w-7">
            <p>&copy; {{ now()->year }} ORYZA, une plateforme de l'ADERIZ (Agence pour le Développement de la Filière Riz)</p>
        </div>
        <p>Tous droits réservés</p>
    </div>
</footer>

</body>
</html>
