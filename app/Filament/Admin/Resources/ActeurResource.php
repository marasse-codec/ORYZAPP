<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActeurResource\Pages;
use App\Filament\Admin\Resources\ActeurResource\RelationManagers;
use App\Models\Acteur;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActeurResource extends Resource
{
    protected static ?string $model = Acteur::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gestion des Acteurs';

    protected static ?string $modelLabel = 'Acteur';
    protected static ?string $pluralModelLabel = 'Acteurs / Demandeurs';

    protected static ?string $navigationLabel = 'Tous les Acteurs';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Organisation du formulaire en section ergonomique
                Forms\Components\Section::make('Informations de l\'Acteur')
                    ->description('Saisissez les coordonnées de l\'acteur ou de la structure.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom complet ou Raison sociale')
                            ->placeholder('Ex: Koffi Emmanuel ou Coopérative ADERIZ')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telephone')
                            ->label('Numéro de Téléphone')
                            ->placeholder('Ex: +225 07 00 00 00 00')
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Adresse E-mail')
                            ->placeholder('Ex: exemple@domaine.ci')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2), // Organise en 2 colonnes
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_acteur')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom / Structure')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->copyable() // Permet de copier au clic !
                    ->copyMessage('Numéro de téléphone copié'),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->default('—'), // Affiche un tiret si vide

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date de création')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                    // 2. Filtre à 3 états : Tous / Oui / Non (ex: a un email ou non)
                    TernaryFilter::make('email')
                        ->label('Adresse E-mail renseignée')
                        ->nullable(),

                    // 3. Filtre personnalisé complexe avec requête Eloquent
                    Filter::make('crees_ce_mois')
                        ->label('Inscrits ce mois-ci')
                        ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
            ])
            // 3. Les Actions sur chaque ligne (Row Actions)
            ->actions([
                // Action 1: Consultation rapide dans une modale
                Tables\Actions\ViewAction::make()
                    ->label('Consulter')
                    ->modalHeading('Fiche de l\'acteur'),

                // Action 2: Modification
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),

                // Action 4: Suppression
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->modalHeading('Supprimer cet acteur ?')
                    ->modalDescription('Cette action est irréversible.'),
            ])
            // 4. Les Actions groupées (Bulk Actions)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ])->label('Actions groupées'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActeurs::route('/'),
            'create' => Pages\CreateActeur::route('/create'),
            'edit' => Pages\EditActeur::route('/{record}/edit'),
        ];
    }
}
