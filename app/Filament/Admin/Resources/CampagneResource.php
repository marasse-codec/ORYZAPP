<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Services\ClotureCampagneService;
use App\Filament\Admin\Resources\CampagneResource\Pages;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Les campagnes : leurs dates, leurs objectifs (comparés au réalisé dans les
 * statistiques et le tableau de pilotage) et leur clôture.
 */
class CampagneResource extends ReferentielResource
{
    protected static ?string $model = Campagne::class;

    protected static ?string $modelLabel = "campagne";

    protected static ?string $pluralModelLabel = "campagnes";

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_campagne')->label('Libellé')->required()->maxLength(30),
            Forms\Components\DatePicker::make('mois_debut_campagne')->label('Début')->required(),
            Forms\Components\DatePicker::make('mois_fin_campagne')->label('Fin')->required()->afterOrEqual('mois_debut_campagne'),

            // Les objectifs sont facultatifs : un objectif vide n'est simplement pas suivi.
            Forms\Components\Section::make('Objectifs de la campagne')
                ->description('Ce que la campagne vise. Les statistiques et le tableau de pilotage comparent le réalisé à ces cibles.')
                ->columns(2)
                ->schema([
                    static::champPourcentage('objectif_taux_attribution', "Taux d'attribution visé (% des demandes soumises)"),
                    static::champPourcentage('objectif_taux_traitement', 'Superficie traitée visée (% de la superficie concernée)'),
                    static::champPourcentage('objectif_taux_recouvrement', 'Taux de recouvrement visé (% du montant dû encaissé)'),
                    Forms\Components\TextInput::make('objectif_delai_arbitrage')->label('Délai soumission → arbitrage, au plus (jours)')
                        ->numeric()->integer()->minValue(1)->maxValue(365),
                ]),
        ];
    }

    private static function champPourcentage(string $nom, string $libelle): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($nom)->label($libelle)->numeric()->minValue(0)->maxValue(100)->suffix('%');
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_campagne')->label('Libellé')->searchable(),
            Tables\Columns\TextColumn::make('mois_debut_campagne')->label('Début')->date('m/Y')->sortable(),
            Tables\Columns\TextColumn::make('mois_fin_campagne')->label('Fin')->date('m/Y')->sortable(),
            Tables\Columns\TextColumn::make('cloturee_le')->label('État')
                ->formatStateUsing(fn ($state) => 'Clôturée le '.$state->format('d/m/Y'))
                ->placeholder('Ouverte')->badge()->color(fn ($state) => $state ? 'gray' : 'success'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::colonnes())
            ->actions([
                static::actionModifier()->hidden(fn (Campagne $record) => $record->estCloturee()),
                Tables\Actions\Action::make('cloturer')
                    ->label('Clôturer')->icon('heroicon-o-lock-closed')->color('warning')
                    ->hidden(fn (Campagne $record) => $record->estCloturee())
                    ->requiresConfirmation()
                    ->modalDescription(function (Campagne $record) {
                        $enCours = app(ClotureCampagneService::class)->enCours($record);

                        return "Après la clôture, plus aucune demande, attribution ni rendement ne pourra être enregistré ou modifié pour cette campagne (les règlements restent possibles). "
                            ."Encore en cours : {$enCours['brouillons']} brouillon(s), {$enCours['a_arbitrer']} demande(s) à arbitrer.";
                    })
                    ->action(fn (Campagne $record) => static::executer(fn () => app(ClotureCampagneService::class)->cloturer($record), 'Campagne clôturée')),
                Tables\Actions\Action::make('rouvrir')
                    ->label('Rouvrir')->icon('heroicon-o-lock-open')
                    ->visible(fn (Campagne $record) => $record->estCloturee())
                    ->form([Forms\Components\TextInput::make('motif')->label('Pourquoi rouvrir ?')->required()->maxLength(255)])
                    ->action(fn (Campagne $record, array $data) => static::executer(fn () => app(ClotureCampagneService::class)->rouvrir($record, $data['motif']), 'Campagne rouverte')),
                static::actionSupprimer(),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCampagnes::route('/')];
    }
}
