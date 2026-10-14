<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Filament\Admin\Resources\JournalActionResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Le journal des actions, en lecture seule : une trace qu'on pourrait corriger
 * ou effacer n'en serait pas une.
 */
class JournalActionResource extends Resource
{
    protected static ?string $model = JournalAction::class;

    protected static ?string $navigationGroup = 'Système';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'action';

    protected static ?string $pluralModelLabel = 'journal des actions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionEnum::voirJournal->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Auteur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->formatStateUsing(fn (string $state) => ActionJournalEnum::tryFrom($state)?->libelle() ?? $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('cible_type')
                    ->label('Objet')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                Tables\Columns\TextColumn::make('cible_id')
                    ->label('N°')
                    ->sortable(),
                Tables\Columns\TextColumn::make('details')
                    ->label('Détails')
                    ->formatStateUsing(fn ($state) => is_array($state) ? collect($state)->map(fn ($v, $k) => "{$k} : {$v}")->implode(', ') : $state)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options(collect(ActionJournalEnum::cases())->mapWithKeys(fn (ActionJournalEnum $a) => [$a->value => $a->libelle()])->all()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Auteur')
                    ->relationship('user', 'name'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalActions::route('/'),
        ];
    }
}
