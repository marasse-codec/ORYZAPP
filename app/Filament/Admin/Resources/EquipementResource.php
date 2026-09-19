<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EquipementResource\Pages;
use App\Filament\Admin\Resources\EquipementResource\RelationManagers;
use App\Models\Equipement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipementResource extends Resource
{
    protected static ?string $model = Equipement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('matricule_equipement')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('etat')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('en_service')
                    ->required(),
                Forms\Components\TextInput::make('id_appui')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricule_equipement')
                    ->searchable(),
                Tables\Columns\TextColumn::make('etat')
                    ->searchable(),
                Tables\Columns\IconColumn::make('en_service')
                    ->boolean(),
                Tables\Columns\TextColumn::make('id_appui')
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
            'index' => Pages\ListEquipements::route('/'),
            'create' => Pages\CreateEquipement::route('/create'),
            'edit' => Pages\EditEquipement::route('/{record}/edit'),
        ];
    }
}
