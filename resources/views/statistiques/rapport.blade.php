{{--
    Rapport de campagne (PDF, dompdf). Tableaux simples : dompdf ne dessine pas les
    graphiques Chart.js, les chiffres sont donc présentés en tableaux.
--}}
@php
    $nombre = fn ($v, $d = 0) => $v === null ? '-' : number_format($v, $d, ',', ' ');
    $logo = public_path('images/brand/aderiz_logo.png');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de campagne {{ $campagne->lib_campagne }}</title>
    <style>
        @page { margin: 1.6cm 1.5cm; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9.5pt; color: #1a1a1a; }
        h1 { font-size: 16pt; color: #1E5E3A; margin: 0 0 4px; }
        h2 { font-size: 11.5pt; color: #1E5E3A; border-bottom: 1px solid #1E5E3A; padding-bottom: 2px; margin: 18px 0 8px; }
        .sous-titre { color: #555; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #d4d4d4; padding: 4px 6px; text-align: left; }
        th { background: #eef7f1; }
        td.n, th.n { text-align: right; }
        .note { color: #666; font-size: 8pt; }
        .entete td { border: none; padding: 0; vertical-align: top; }
    </style>
</head>
<body>
    <table class="entete">
        <tr>
            <td>
                <h1>Rapport de campagne : {{ $campagne->lib_campagne }}</h1>
                <p class="sous-titre">Du {{ $campagne->mois_debut_campagne->format('d/m/Y') }} au {{ $campagne->mois_fin_campagne->format('d/m/Y') }}
                    · {{ $campagne->estCloturee() ? 'clôturée le '.$campagne->cloturee_le->format('d/m/Y').' (chiffres définitifs)' : 'en cours (chiffres provisoires)' }}</p>
                <p class="sous-titre">Périmètre : {{ $descriptionFiltre }}</p>
                <p class="note">Édité le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()?->name }}</p>
            </td>
            @if (file_exists($logo))
                <td style="text-align: right; width: 120px;"><img src="{{ $logo }}" style="height: 55px;"></td>
            @endif
        </tr>
    </table>

    <h2>Chiffres clés</h2>
    <table>
        <tr><th>Demandes soumises</th><td class="n">{{ $nombre($indicateurs['soumises']) }}</td>
            <th>Attribuées</th><td class="n">{{ $nombre($indicateurs['attribuees']) }} ({{ $nombre($indicateurs['taux_attribution'], 1) }} %)</td></tr>
        <tr><th>Rejetées</th><td class="n">{{ $nombre($indicateurs['rejetees']) }}</td>
            <th>Bénéficiaires servis</th><td class="n">{{ $nombre($indicateurs['beneficiaires']) }}</td></tr>
        <tr><th>Superficie concernée</th><td class="n">{{ $nombre($indicateurs['superficie_concernee'], 2) }} ha</td>
            <th>Superficie traitée</th><td class="n">{{ $nombre($indicateurs['superficie_traitee'], 2) }} ha ({{ $nombre($indicateurs['taux_traitement'], 1) }} %)</td></tr>
        <tr><th>Délai soumission → arbitrage</th><td class="n">{{ $nombre($indicateurs['delai_arbitrage'], 1) }} j</td>
            <th>Délai arbitrage → retrait</th><td class="n">{{ $nombre($indicateurs['delai_retrait'], 1) }} j</td></tr>
    </table>

    <h2>Finances</h2>
    <table>
        <tr><th>Valeur attribuée</th><td class="n">{{ $nombre($finances['engage']) }} F</td>
            <th>Montant dû (publié)</th><td class="n">{{ $nombre($finances['du']) }} F</td></tr>
        <tr><th>Encaissé</th><td class="n">{{ $nombre($finances['encaisse']) }} F</td>
            <th>Taux de recouvrement</th><td class="n">{{ $nombre($finances['taux_recouvrement'], 1) }} %</td></tr>
    </table>

    <h2>Superficies et rendements par écologie</h2>
    <table>
        <tr><th>Écologie</th><th class="n">Concernée (ha)</th><th class="n">Traitée (ha)</th><th class="n">Rendement (t/ha)</th><th class="n">Retours</th></tr>
        @foreach ($superficies as $ecologie => $s)
            @php $r = $rendements[$ecologie] ?? null; @endphp
            <tr>
                <td>{{ $ecologie }}</td>
                <td class="n">{{ $nombre($s['concernee'], 2) }}</td>
                <td class="n">{{ $nombre($s['traitee'], 2) }}</td>
                <td class="n">{{ $nombre($r['rendement'] ?? null, 2) }}</td>
                <td class="n">{{ $r ? $r['renseignees'].'/'.$r['declarees'] : '-' }}</td>
            </tr>
        @endforeach
    </table>
    <p class="note">Rendement = production totale ÷ superficie récoltée totale (moyenne pondérée), d'après les retours des délégués de pôle.</p>

    <h2>Satisfaction des quantités demandées</h2>
    @if ($satisfaction->isEmpty())
        <p>Aucune ligne de demande.</p>
    @else
        <table>
            <tr><th>Appui</th><th class="n">Demandé</th><th class="n">Attribué</th><th class="n">Taux</th></tr>
            @foreach ($satisfaction as $ligne)
                <tr>
                    <td>{{ $ligne['appui'] }}</td>
                    <td class="n">{{ $nombre($ligne['demandee']) }}</td>
                    <td class="n">{{ $nombre($ligne['attribuee']) }}</td>
                    <td class="n">{{ $ligne['taux'] !== null ? $nombre($ligne['taux'], 1).' %' : '-' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Répartition</h2>
    <table>
        <tr><th style="width: 50%">Localités les plus demandeuses</th><th>Bénéficiaires par type d'acteur</th></tr>
        <tr>
            <td style="vertical-align: top">
                @forelse ($parLocalite as $localite => $total) {{ $localite }} : {{ $total }}<br> @empty - @endforelse
            </td>
            <td style="vertical-align: top">
                @forelse ($parTypeActeur as $type => $total) {{ $type }} : {{ $total }}<br> @empty - @endforelse
            </td>
        </tr>
    </table>

    <p class="note">Population statistique : les demandes soumises de la campagne{{ $filtre->estVide() ? '' : ', restreintes par le périmètre indiqué en tête' }}. Les brouillons et les attributions annulées sont exclus.</p>
</body>
</html>
