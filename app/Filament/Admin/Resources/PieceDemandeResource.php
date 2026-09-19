<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PieceDemandeResource\Pages;
use App\Filament\Admin\Resources\PieceDemandeResource\RelationManagers;
use App\Models\PieceDemande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PieceDemandeResource extends Resource
{
    protected static ?string $model = PieceDemande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('chemin_fichier')
                    ->required()
                    ->maxLength(250),
                Forms\Components\TextInput::make('id_demande')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_type_piece')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chemin_fichier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_demande')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_type_piece')
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
            'index' => Pages\ListPieceDemandes::route('/'),
            'create' => Pages\CreatePieceDemande::route('/create'),
            'edit' => Pages\EditPieceDemande::route('/{record}/edit'),
        ];
    }
}
