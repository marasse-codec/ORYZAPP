<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Filament\Admin\Resources\JournalActionResource\Pages;
use Filament\Forms;
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
                    ->placeholder('Inconnu')
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
                // « champ : avant → après » pour une modification, « champ : valeur » sinon.
                Tables\Columns\TextColumn::make('details')
                    ->label('Détails')
                    ->getStateUsing(fn (JournalAction $record) => $record->detailsLisibles() ?: '-')
                    ->wrap(),
                Tables\Columns\TextColumn::make('adresse_ip')
                    ->label('Adresse IP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options(collect(ActionJournalEnum::cases())->mapWithKeys(fn (ActionJournalEnum $a) => [$a->value => $a->libelle()])->all()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Auteur')
                    ->relationship('user', 'name'),
                // Retrouver ce qui s'est passé sur une période (audit, contrôle).
                Tables\Filters\Filter::make('periode')
                    ->form([
                        Forms\Components\DatePicker::make('du')->label('Du'),
                        Forms\Components\DatePicker::make('au')->label('Au'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['du'] ?? null, fn ($q, $du) => $q->whereDate('created_at', '>=', $du))
                        ->when($data['au'] ?? null, fn ($q, $au) => $q->whereDate('created_at', '<=', $au))),
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
