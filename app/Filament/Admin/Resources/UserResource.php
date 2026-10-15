<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Acces\Services\CompteService;
use App\Domain\Referentiel\Models\Departement;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

/**
 * Les comptes utilisateurs : identité, rôle (un seul par compte) et, pour un agent
 * de service, son service. Un compte ne se supprime pas : il se désactive, et
 * son historique reste attaché à son nom.
 */
class UserResource extends ReferentielResource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?string $navigationGroup = 'Accès';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    /** Les options de rôle : valeur stockée => libellé. */
    public static function roles(): array
    {
        return collect(RoleEnum::cases())->mapWithKeys(fn (RoleEnum $r) => [$r->value => $r->libelle()])->all();
    }

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')->label('Mot de passe')->password()->revealable()
                ->rule(Password::defaults())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('À la modification, laissez vide pour le conserver. Règles : voir « Paramètres généraux ».'),
            Forms\Components\Select::make('role')->label('Rôle (un seul par compte)')->options(static::roles())->required()->live(),
            Forms\Components\Select::make('id_service')->label('Service')
                ->relationship('service', 'lib_service')->searchable()->preload()
                ->visible(fn (Get $get) => $get('role') === RoleEnum::agentService->value)
                ->required(fn (Get $get) => $get('role') === RoleEnum::agentService->value),
            // Le DP est responsable des demandes des départements qu'il couvre.
            Forms\Components\Select::make('departements')->label('Départements couverts')
                ->multiple()->searchable()->preload()
                ->options(fn () => Departement::orderBy('lib_departement')->pluck('lib_departement', 'id_departement'))
                ->visible(fn (Get $get) => $get('role') === RoleEnum::dp->value)
                ->required(fn (Get $get) => $get('role') === RoleEnum::dp->value),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label('Rôle')->badge()
                ->formatStateUsing(fn (?string $state) => RoleEnum::tryFrom((string) $state)?->libelle() ?? $state),
            Tables\Columns\TextColumn::make('service.lib_service')->label('Service'),
            Tables\Columns\TextColumn::make('departements.lib_departement')->label('Départements')->badge()->toggleable(),
            Tables\Columns\IconColumn::make('actif')->label('Actif')->boolean(),
            Tables\Columns\TextColumn::make('derniere_connexion_le')->label('Dernière connexion')->dateTime('d/m/Y H:i')->placeholder('Jamais')->sortable(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::colonnes())
            ->filters([
                Tables\Filters\TernaryFilter::make('actif')->label('Comptes actifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, User $record) => $data + [
                        'role' => $record->roles->first()?->name,
                        'departements' => $record->departements->pluck('id_departement')->all(),
                    ])
                    ->using(fn (Model $record, array $data) => app(CompteService::class)->modifier($record, $data)),
                // Désactiver plutôt que supprimer : la personne ne se connecte plus, son historique reste.
                Tables\Actions\Action::make('desactiver')
                    ->label('Désactiver')->icon('heroicon-o-lock-closed')->color('danger')
                    ->visible(fn (User $record) => $record->estActif() && ! $record->is(auth()->user()))
                    ->form([Forms\Components\TextInput::make('motif')->label('Motif (facultatif)')->maxLength(255)])
                    ->requiresConfirmation()
                    ->action(fn (User $record, array $data) => static::executer(fn () => app(CompteService::class)->desactiver($record, $data['motif'] ?? null), 'Compte désactivé')),
                Tables\Actions\Action::make('reactiver')
                    ->label('Réactiver')->icon('heroicon-o-lock-open')->color('success')
                    ->visible(fn (User $record) => ! $record->estActif())
                    ->requiresConfirmation()
                    ->action(fn (User $record) => static::executer(fn () => app(CompteService::class)->reactiver($record), 'Compte réactivé')),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
