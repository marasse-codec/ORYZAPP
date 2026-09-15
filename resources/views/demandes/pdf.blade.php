<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande N° DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 12mm 15mm;
            size: A4 portrait;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1B4D3E;
            line-height: 1.4;
        }

        /* En-tête de page */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1B4D3E;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1B4D3E;
            margin: 0;
        }

        .subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            background-color: #f1f5f9;
            color: #334155;
            text-align: right;
        }

        /* Cartouches / Sections */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1B4D3E;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Grilles d'informations (basées sur des tables) */
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }

        .value {
            font-size: 11px;
            color: #0f172a;
        }

        /* Tables de données */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
        }

        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }

        /* Alertes (Rejet, Commentaires) */
        .alert-rejet {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .comment-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 4px;
            font-size: 10px;
            color: #475569;
            margin-top: 6px;
        }

        /* Pied de page */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    {{-- En-tête officiel --}}
    <table class="header-table">
        <tr>
            <td>
                <h1 class="title">DEMANDE N° DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}</h1>
                <div class="subtitle">
                    Déposée le {{ $demande->date_demande?->format('d/m/Y') }} · 
                    Mise en place souhaitée le {{ $demande->debut_periode_mise_en_place?->format('d/m/Y') }}
                </div>
            </td>
            <td class="text-right" style="vertical-align: top;">
                <div class="status-badge">
                    {{ $demande->id_statut_demande->libelle() }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Motif de rejet le cas échéant --}}
    @if ($demande->motifs_rejet)
        <div class="alert-rejet">
            <strong>Demande rejetée le {{ $demande->date_rejet?->format('d/m/Y') }}</strong><br>
            {{ $demande->motifs_rejet }}
        </div>
    @endif

    {{-- Demandeur --}}
    <div class="section-title">Informations du demandeur</div>
    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="label">Nom & Prénoms</div>
                <div class="value">{{ $demande->acteur?->nom }}</div>
            </td>
            <td width="50%">
                <div class="label">Profil</div>
                <div class="value">{{ $demande->typeActeur?->lib_type_acteur }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 6px;">
                <div class="label">Téléphone</div>
                <div class="value">{{ $demande->acteur?->telephone }}</div>
            </td>
            <td style="padding-top: 6px;">
                <div class="label">E-mail</div>
                <div class="value">{{ $demande->acteur?->email ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 6px;">
                <div class="label">Localité</div>
                <div class="value">
                    {{ $demande->localite?->lib_localite }} 
                    ({{ $demande->localite?->departement?->lib_departement }})
                </div>
            </td>
        </tr>
    </table>

    {{-- Systèmes de production --}}
    <div class="section-title">Systèmes de production</div>
    @if ($demande->systemesProduction->isEmpty())
        <p style="color: #64748b; font-style: italic;">Aucune superficie déclarée.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Système</th>
                    <th class="text-right">Superficie (ha)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($demande->systemesProduction as $systeme)
                    <tr>
                        <td>{{ $systeme->typeSystemeProduction?->lib_type_systeme_production }}</td>
                        <td class="text-right">{{ number_format($systeme->superficie, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right">{{ number_format($demande->systemesProduction->sum('superficie'), 2, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- Appuis sollicités --}}
    <div class="section-title">Appuis sollicités</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Appui</th>
                <th>Service</th>
                <th class="text-right">Quantité</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($demande->lignes as $ligne)
                <tr>
                    <td>
                        <strong>{{ $ligne->appui?->libelle }}</strong><br>
                        <span style="font-size: 8px; color: #64748b;">{{ $ligne->appui?->typeAppui?->libelle }}</span>
                    </td>
                    <td>{{ $ligne->appui?->typeAppui?->service?->lib_service }}</td>
                    <td class="text-right">
                        {{ $ligne->quantite_demandee }} {{ $ligne->appui?->typeAppui?->uniteTypeAppui?->lib_unite_type_appui }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Attribution si disponible --}}
    @if ($demande->attribution)
        <div class="avoid-break">
            <div class="section-title">Attribution accordée</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Appui</th>
                        <th class="text-right">Quantité attribuée</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($demande->attribution->lignesAttributions as $ligne)
                        <tr>
                            <td>{{ $ligne->appui?->libelle }}</td>
                            <td class="text-right">{{ $ligne->quantite_attribuee }}</td>
                            <td class="text-right">{{ number_format($ligne->montant(), 0, ',', ' ') }} F CFA</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Montant Total</td>
                        <td class="text-right">{{ number_format($demande->attribution->montantTotal(), 0, ',', ' ') }} F CFA</td>
                    </tr>
                </tfoot>
            </table>
            @if ($demande->attribution->commentaires)
                <div class="comment-box">
                    <strong>Commentaire :</strong> {{ $demande->attribution->commentaires }}
                </div>
            @endif
        </div>
    @endif

    {{-- Historique d'instruction --}}
    @if ($demande->rapports->isNotEmpty())
        <div class="avoid-break">
            <div class="section-title">Avis des services d'instruction</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Statut / Date</th>
                        <th>Commentaire</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($demande->rapports as $rapport)
                        <tr>
                            <td><strong>{{ $rapport->service?->lib_service }}</strong></td>
                            <td>
                                {{ $rapport->estAccorde() ? 'Autorisé le '.$rapport->accord_service->format('d/m/Y') : 'En cours' }}
                            </td>
                            <td>{{ $rapport->commentaire ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Footer automatique --}}
    <div class="footer">
        Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }} · Page 1/1
    </div>

</body>
</html>