<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TypeAppuiResource\Pages;
use App\Filament\Admin\Resources\TypeAppuiResource\RelationManagers;
use App\Models\TypeAppui;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypeAppuiResource extends Resource
{
    protected static ?string $model = TypeAppui::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('lib_type_appui')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Toggle::make('est_intrant')
                    ->required(),
                Forms\Components\TextInput::make('id_service')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lib_type_appui')
                    ->searchable(),
                Tables\Columns\IconColumn::make('est_intrant')
                    ->boolean(),
                Tables\Columns\TextColumn::make('id_service')
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
            'index' => Pages\ListTypeAppuis::route('/'),
            'create' => Pages\CreateTypeAppui::route('/create'),
            'edit' => Pages\EditTypeAppui::route('/{record}/edit'),
        ];
    }
}
