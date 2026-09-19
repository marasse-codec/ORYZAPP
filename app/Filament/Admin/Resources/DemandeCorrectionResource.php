<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DemandeCorrectionResource\Pages;
use App\Filament\Admin\Resources\DemandeCorrectionResource\RelationManagers;
use App\Models\DemandeCorrection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DemandeCorrectionResource extends Resource
{
    protected static ?string $model = DemandeCorrection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id_demande')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_traitement')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('motif')
                    ->required()
                    ->maxLength(300),
                Forms\Components\DateTimePicker::make('date_demande_correction')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_demande')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_traitement')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motif')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_demande_correction')
                    ->dateTime()
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
            'index' => Pages\ListDemandeCorrections::route('/'),
            'create' => Pages\CreateDemandeCorrection::route('/create'),
            'edit' => Pages\EditDemandeCorrection::route('/{record}/edit'),
        ];
    }
}
