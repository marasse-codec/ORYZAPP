<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TypeActeurResource\Pages;
use App\Filament\Admin\Resources\TypeActeurResource\RelationManagers;
use App\Models\TypeActeur;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypeActeurResource extends Resource
{
    protected static ?string $model = TypeActeur::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code_type_acteur')
                    ->required()
                    ->maxLength(3),
                Forms\Components\TextInput::make('lib_type_acteur')
                    ->required()
                    ->maxLength(60),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code_type_acteur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lib_type_acteur')
                    ->searchable(),
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
            'index' => Pages\ListTypeActeurs::route('/'),
            'create' => Pages\CreateTypeActeur::route('/create'),
            'edit' => Pages\EditTypeActeur::route('/{record}/edit'),
        ];
    }
}
