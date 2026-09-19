<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CampagneResource\Pages;
use App\Filament\Admin\Resources\CampagneResource\RelationManagers;
use App\Models\Campagne;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampagneResource extends Resource
{
    protected static ?string $model = Campagne::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('lib_campagne')
                    ->required()
                    ->maxLength(30),
                Forms\Components\DatePicker::make('date_debut_campagne')
                    ->required(),
                Forms\Components\DatePicker::make('date_fin_campagne')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lib_campagne')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_debut_campagne')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_fin_campagne')
                    ->date()
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
            'index' => Pages\ListCampagnes::route('/'),
            'create' => Pages\CreateCampagne::route('/create'),
            'edit' => Pages\EditCampagne::route('/{record}/edit'),
        ];
    }
}
