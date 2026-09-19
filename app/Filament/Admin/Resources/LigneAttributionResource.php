<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LigneAttributionResource\Pages;
use App\Filament\Admin\Resources\LigneAttributionResource\RelationManagers;
use App\Models\LigneAttribution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LigneAttributionResource extends Resource
{
    protected static ?string $model = LigneAttribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id_attribution')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_appui')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantite_attribuee')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('cout_attribution')
                    ->numeric(),
                Forms\Components\TextInput::make('remise')
                    ->required()
                    ->numeric()
                    ->default(0.00),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_attribution')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_appui')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantite_attribuee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cout_attribution')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remise')
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
            'index' => Pages\ListLigneAttributions::route('/'),
            'create' => Pages\CreateLigneAttribution::route('/create'),
            'edit' => Pages\EditLigneAttribution::route('/{record}/edit'),
        ];
    }
}
