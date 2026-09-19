<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ApprovisionnementResource\Pages;
use App\Filament\Admin\Resources\ApprovisionnementResource\RelationManagers;
use App\Models\Approvisionnement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovisionnementResource extends Resource
{
    protected static ?string $model = Approvisionnement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date_approvisionnement')
                    ->required(),
                Forms\Components\TextInput::make('reference_commande')
                    ->maxLength(255),
                Forms\Components\TextInput::make('origine')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_approvisionnement')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_commande')
                    ->searchable(),
                Tables\Columns\TextColumn::make('origine')
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
            'index' => Pages\ListApprovisionnements::route('/'),
            'create' => Pages\CreateApprovisionnement::route('/create'),
            'edit' => Pages\EditApprovisionnement::route('/{record}/edit'),
        ];
    }
}
