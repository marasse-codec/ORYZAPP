<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MouvementStockResource\Pages;
use App\Filament\Admin\Resources\MouvementStockResource\RelationManagers;
use App\Models\MouvementStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MouvementStockResource extends Resource
{
    protected static ?string $model = MouvementStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('quantite')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('date_mouvement')
                    ->required(),
                Forms\Components\TextInput::make('motif')
                    ->maxLength(255),
                Forms\Components\TextInput::make('id_type_mouvement_stock')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_appui')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_equipement')
                    ->numeric(),
                Forms\Components\TextInput::make('id_agent')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_approvisionnement')
                    ->numeric(),
                Forms\Components\TextInput::make('id_ligne_attribution')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quantite')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_mouvement')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motif')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_type_mouvement_stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_appui')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_equipement')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_agent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_approvisionnement')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_ligne_attribution')
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
            'index' => Pages\ListMouvementStocks::route('/'),
            'create' => Pages\CreateMouvementStock::route('/create'),
            'edit' => Pages\EditMouvementStock::route('/{record}/edit'),
        ];
    }
}
