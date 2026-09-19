<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TraitementResource\Pages;
use App\Filament\Admin\Resources\TraitementResource\RelationManagers;
use App\Models\Traitement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TraitementResource extends Resource
{
    protected static ?string $model = Traitement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('observation_interne')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('date_fin_traitement'),
                Forms\Components\TextInput::make('motifs_rejet')
                    ->maxLength(255),
                Forms\Components\TextInput::make('id_demande')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_service')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('observation_interne')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_fin_traitement')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motifs_rejet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_demande')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_service')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTraitements::route('/'),
            'create' => Pages\CreateTraitement::route('/create'),
            'edit' => Pages\EditTraitement::route('/{record}/edit'),
        ];
    }
}
