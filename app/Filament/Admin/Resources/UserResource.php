<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Acces\Services\CompteService;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

/** Les comptes utilisateurs : identité, rôle (un seul) et, pour un agent de service, son service. */
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
                ->rule(Password::min(8))
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('À la modification, laissez vide pour le conserver.'),
            Forms\Components\Select::make('role')->label('Rôle')->options(static::roles())->required()->live(),
            Forms\Components\Select::make('id_service')->label('Service')
                ->relationship('service', 'lib_service')->searchable()->preload()
                ->visible(fn (Get $get) => $get('role') === RoleEnum::agentService->value)
                ->required(fn (Get $get) => $get('role') === RoleEnum::agentService->value),
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
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::colonnes())
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, User $record) => $data + ['role' => $record->roles->first()?->name])
                    ->using(fn (Model $record, array $data) => app(CompteService::class)->modifier($record, $data)),
                // On ne se supprime pas soi-même.
                static::actionSupprimer()->hidden(fn (User $record) => $record->is(auth()->user())),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
