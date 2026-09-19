<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SystemeProductionResource\Pages;
use App\Filament\Admin\Resources\SystemeProductionResource\RelationManagers;
use App\Models\SystemeProduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SystemeProductionResource extends Resource
{
    protected static ?string $model = SystemeProduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('superficie')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_demande')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_type_systeme_production')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('superficie')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_demande')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_type_systeme_production')
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
            'index' => Pages\ListSystemeProductions::route('/'),
            'create' => Pages\CreateSystemeProduction::route('/create'),
            'edit' => Pages\EditSystemeProduction::route('/{record}/edit'),
        ];
    }
}
