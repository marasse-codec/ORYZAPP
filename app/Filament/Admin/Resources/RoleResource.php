<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Acces\Services\DroitService;
use App\Filament\Admin\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

/**
 * « Rôles et droits » : pour chaque rôle, la liste des droits qu'il accorde,
 * à cocher ou décocher. Les rôles eux-mêmes sont fixés par l'application
 * (RoleEnum) : on n'en crée ni n'en supprime ici.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $modelLabel = 'rôle';

    protected static ?string $pluralModelLabel = 'rôles et droits';

    protected static ?string $navigationGroup = 'Accès';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionEnum::gererRessources->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\CheckboxList::make('droits')
                ->label('Droits accordés')
                ->options(fn () => app(DroitService::class)->catalogue())
                ->columns(2)
                ->bulkToggleable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Rôle')
                    ->formatStateUsing(fn (string $state) => RoleEnum::tryFrom($state)?->libelle() ?? $state),
                Tables\Columns\TextColumn::make('permissions_count')->label('Droits')->counts('permissions'),
                Tables\Columns\TextColumn::make('users_count')->label('Comptes')->counts('users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Droits')
                    ->modalHeading(fn (Role $record) => 'Droits du rôle « '.(RoleEnum::tryFrom($record->name)?->libelle() ?? $record->name).' »')
                    ->hidden(fn (Role $record) => ! app(DroitService::class)->estModifiable($record))
                    ->mutateRecordDataUsing(fn (array $data, Role $record) => ['droits' => $record->permissions->pluck('name')->all()])
                    ->using(function (Role $record, array $data) {
                        app(DroitService::class)->modifier($record, $data['droits'] ?? []);

                        return $record;
                    }),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRoles::route('/')];
    }
}
