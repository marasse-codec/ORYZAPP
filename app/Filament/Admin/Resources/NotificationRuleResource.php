<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NotificationRuleResource\Pages;
use App\Filament\Admin\Resources\NotificationRuleResource\RelationManagers;
use App\Models\NotificationRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationRuleResource extends Resource
{
    protected static ?string $model = NotificationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cible & Événement')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la règle')
                            ->placeholder('Ex: Alerte Seuil Stock')
                            ->required(),

                        Forms\Components\Select::make('resource_type')
                            ->label('Ressource à surveiller')
                            ->options([
                                'App\Models\Appui' => 'Appui (Stock)',
                                'App\Models\Demande' => 'Demande',
                                'App\Models\Attribution' => 'Attribution',
                                'App\Models\MouvementStock' => 'Mouvement de Stock',
                            ])
                            ->required(),

                        Forms\Components\Select::make('event')
                            ->label('Événement déclencheur')
                            ->options([
                                'updated' => 'Mise à jour (Updated)',
                                'created' => 'Création (Created)',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activer cette règle')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Conditions du Déclenchement')
                    ->description('Laissez vide si vous souhaitez être alerté à chaque événement.')
                    ->schema([
                        Forms\Components\TextInput::make('field')
                            ->label('Champ BDD à vérifier')
                            ->placeholder('Ex: quantite, statut'),

                        Forms\Components\Select::make('operator')
                            ->label('Opérateur')
                            ->options([
                                '<' => 'Inférieur à (<)',
                                '>' => 'Supérieur à (>)',
                                '=' => 'Égal à (=)',
                                'changed' => 'Valeur modifiée (changed)',
                                'is_not_null' => 'valeur non null'
                            ]),

                        Forms\Components\TextInput::make('value')
                            ->label('Valeur cible')
                            ->placeholder('Ex: 5, valide, etc.'),
                    ])->columns(3),

                Forms\Components\Section::make('Modèle du Message Push')
                    ->description('Utilisez les accolades pour insérer dynamiquement des valeurs : {libelle}, {quantite}, {code}')
                    ->schema([
                        Forms\Components\TextInput::make('title_template')
                            ->label('Titre du Push')
                            ->required()
                            ->placeholder('Ex: ⚠️ Stock Critique : {libelle}'),

                        Forms\Components\Textarea::make('message_template')
                            ->label('Corps du message')
                            ->required()
                            ->rows(2)
                            ->placeholder('Ex: Attention, il ne reste que {quantite} unités en stock.'),
                    ]),

                Forms\Components\Select::make('recipient_type')
                ->label('Qui doit être notifié ?')
                ->options([
                    'subscribers' => 'Uniquement les abonnés à la ressource',
                    'roles'       => 'Tous les utilisateurs ayant un rôle précis',
                    'users'       => 'Des utilisateurs spécifiques',
                    'relation'    => 'L\'utilisateur lié au modèle (ex: créateur/responsable)',
                ])
                ->required()
                ->live(), // Rend le champ réactif dans Filament v3

            // Si "roles" est sélectionné
            Forms\Components\Select::make('recipient_targets')
                ->label('Sélectionner le(s) rôle(s)')
                ->options(\Spatie\Permission\Models\Role::pluck('name', 'name'))
                ->multiple()
                ->visible(fn ($get) => $get('recipient_type') === 'roles')
                ->required(fn ($get) => $get('recipient_type') === 'roles'),

            // Si "users" est sélectionné
            Forms\Components\Select::make('recipient_targets')
                ->label('Sélectionner les utilisateurs')
                ->options(\App\Models\User::pluck('name', 'id'))
                ->multiple()
                ->searchable()
                ->visible(fn ($get) => $get('recipient_type') === 'users')
                ->required(fn ($get) => $get('recipient_type') === 'users'),

            // Si "relation" est sélectionné
            Forms\Components\Select::make('recipient_targets')
                ->label('Nom de la relation sur le modèle')
                ->options([
                    'user' => 'Créateur de la donnée ($model->user)',
                    'acteur' => 'Responsable affecté ($model->acteur)',
                ])
                ->visible(fn ($get) => $get('recipient_type') === 'relation')
                ->required(fn ($get) => $get('recipient_type') === 'relation'),

            // Dans le Formulaire Filament (NotificationRuleResource)
            Forms\Components\CheckboxList::make('channels')
                ->label('Canaux d\'envoi')
                ->options([
                    'database' => 'Cloche In-App (BDD)',
                    'webpush'  => 'Push Navigateur / PWA',
                    'mail'     => 'Email (Courrier électronique)',
                ])
                ->default(['database', 'webpush'])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('resource_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->searchable(),
                Tables\Columns\TextColumn::make('field')
                    ->searchable(),
                Tables\Columns\TextColumn::make('operator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title_template')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('recipient_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListNotificationRules::route('/'),
            'create' => Pages\CreateNotificationRule::route('/create'),
            'edit' => Pages\EditNotificationRule::route('/{record}/edit'),
        ];
    }
}
