<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LigneDemandeResource\Pages;
use App\Filament\Admin\Resources\LigneDemandeResource\RelationManagers;
use App\Models\LigneDemande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LigneDemandeResource extends Resource
{
    protected static ?string $model = LigneDemande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id_demande')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_type_appui')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantite_demandee')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_demande')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_type_appui')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantite_demandee')
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
            'index' => Pages\ListLigneDemandes::route('/'),
            'create' => Pages\CreateLigneDemande::route('/create'),
            'edit' => Pages\EditLigneDemande::route('/{record}/edit'),
        ];
    }
}
