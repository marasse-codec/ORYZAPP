@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;
    Carbon::setLocale('fr');

    $departements = $demande->localites->pluck('departement.lib_departement')->filter()->unique()->join(', ');
    $localitesStr = $demande->localites->pluck('lib_localite')->filter()->unique()->join(', ');
    $nomExpediteur = $demande->nom_structure ?: ($demande->acteur?->nom ?? 'Nom de l\'entité');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande d'appuis DEM-{{ $demande->id_demande }}</title>
    <style>
        @page {
            margin: 2.5cm 2cm;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
        }
        .header {
            width: 100%;
            margin-bottom: 2rem;
        }
        .expediteur {
            float: left;
            width: 55%;
        }
        .expediteur-item {
            margin-bottom: 3px;
        }
        .destinataire {
            float: right;
            width: 40%;
            text-align: left;
            margin-top: 1.5rem;
            font-weight: bold;
        }
        .clear {
            clear: both;
        }
        .objet {
            margin-top: 2rem;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }
        .contenu {
            text-align: justify;
            margin-bottom: 1.5rem;
        }
        .paragraphe {
            margin-bottom: 1.2rem;
        }
        .signature {
            float: right;
            margin-top: 3rem;
            text-align: center;
            width: 40%;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="expediteur">
            <div class="expediteur-item"><strong>{{ $nomExpediteur }}</strong></div>
            @if ($demande->nom_structure && $demande->acteur?->nom)
                <div class="expediteur-item">Représenté(e) par : {{ $demande->acteur->nom }}</div>
            @endif
            <div class="expediteur-item">Tél : {{ $demande->acteur?->telephone ?? 'Numéro de téléphone' }}</div>
            <div class="expediteur-item">Email/Adresse : {{ $demande->acteur?->email ?? $demande->acteur?->adresse_postale ?? 'Adresse mail et/ou postale' }}</div>
        </div>

        <div class="destinataire">
            <p style="margin: 0;">Date : {{ $demande->date_demande ? $demande->date_demande->format('d/m/Y') : now()->format('d/m/Y') }}</p>
            <br>
            À<br>
            Monsieur le Directeur Général<br>
            de l'Agence pour le Développement<br>
            de la filière Riz (ADERIZ)
        </div>
        <div class="clear"></div>
    </div>

    <div class="objet">
        Objet : Demande d'appuis
    </div>

    <div class="contenu">
        <p class="paragraphe">Monsieur le Directeur,</p>

        <p class="paragraphe">
            Nous disposons d'une parcelle de 
            <strong>{{ number_format($demande->systemesProductions->sum('superficie'), 2, ',', ' ') }} ha</strong> 
            ({{ $demande->systemesProductions->pluck('typeSystemeProduction.lib_type_systeme_production')->filter()->unique()->join(', ') ?: 'bas-fond, plateau ou périmètre irrigué' }}) 
            dans {{ Str::contains($departements, ',') ? 'les Départements de' : 'le Département de' }} <strong>{{ $departements ?: '(............)' }}</strong> 
            plus précisément dans {{ Str::contains($localitesStr, ',') ? 'les localités de' : 'la localité de' }} <strong>{{ $localitesStr ?: '(............)' }}</strong> 
            que nous voudrions mettre en valeur pour la présente campagne.
        </p>

        <p class="paragraphe">
            Pour mener à bien notre activité, nous souhaiterions avoir auprès de votre structure des appuis en 
            <strong>{{ $demande->typesAppuis()->pluck('lib_type_appui')->join(', ', ' et ') ?: '(semence, intrant, encadrement technique, matériel agricole)' }}</strong> 
            pour des mises en place dès 
            <strong>{{ $demande->periode_debut_mise_en_place ? Carbon::parse($demande->periode_debut_mise_en_place)->isoFormat('MMMM YYYY') : '(la période de mise en place)' }}</strong>.
        </p>

        <p class="paragraphe">
            Dans l'attente d'une suite favorable, nous vous prions d'agréer Monsieur le Directeur Général, l'expression de nos salutations distinguées.
        </p>
    </div>

    <div class="signature">
        <p><strong>Signature de l'intéressé</strong></p>
    </div>

</body>
</html>