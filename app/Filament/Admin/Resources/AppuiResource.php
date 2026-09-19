<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppuiResource\Pages;
use App\Filament\Admin\Resources\AppuiResource\RelationManagers;
use App\Models\Appui;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppuiResource extends Resource
{
    protected static ?string $model = Appui::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('libelle')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('quantite_en_stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('cout_unitaire')
                    ->numeric(),
                Forms\Components\TextInput::make('id_type_appui')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantite_en_stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cout_unitaire')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_type_appui')
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
            'index' => Pages\ListAppuis::route('/'),
            'create' => Pages\CreateAppui::route('/create'),
            'edit' => Pages\EditAppui::route('/{record}/edit'),
        ];
    }
}
