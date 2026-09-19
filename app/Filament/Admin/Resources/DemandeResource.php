<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DemandeResource\Pages;
use App\Filament\Admin\Resources\DemandeResource\RelationManagers;
use App\Models\Demande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DemandeResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date_demande')
                    ->required(),
                Forms\Components\DatePicker::make('periode_debut_mise_en_place')
                    ->required(),
                Forms\Components\DatePicker::make('periode_fin_mise_en_place')
                    ->required(),
                Forms\Components\Toggle::make('soumise')
                    ->required(),
                Forms\Components\TextInput::make('id_acteur')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_user')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_type_acteur')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_localite')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_campagne')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_demande')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periode_debut_mise_en_place')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periode_fin_mise_en_place')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('soumise')
                    ->boolean(),
                Tables\Columns\TextColumn::make('id_acteur')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_user')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_type_acteur')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_localite')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_campagne')
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
            'index' => Pages\ListDemandes::route('/'),
            'create' => Pages\CreateDemande::route('/create'),
            'edit' => Pages\EditDemande::route('/{record}/edit'),
        ];
    }
}
