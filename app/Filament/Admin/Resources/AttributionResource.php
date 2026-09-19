<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AttributionResource\Pages;
use App\Filament\Admin\Resources\AttributionResource\RelationManagers;
use App\Models\Attribution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttributionResource extends Resource
{
    protected static ?string $model = Attribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id_demande')
                    ->numeric(),
                Forms\Components\TextInput::make('id_session')
                    ->numeric(),
                Forms\Components\TextInput::make('commentaires')
                    ->maxLength(500),
                Forms\Components\TextInput::make('remise')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\DatePicker::make('date_destockage'),
                Forms\Components\TextInput::make('recipiendaire')
                    ->maxLength(255),
                Forms\Components\TextInput::make('numero_piece_identite')
                    ->maxLength(60),
                Forms\Components\TextInput::make('chemin_piece_identite')
                    ->maxLength(250),
                Forms\Components\TextInput::make('id_statut_attribution')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('date_publication'),
                Forms\Components\TextInput::make('id_traitement')
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
                Tables\Columns\TextColumn::make('id_session')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commentaires')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remise')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_destockage')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipiendaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('numero_piece_identite')
                    ->searchable(),
                Tables\Columns\TextColumn::make('chemin_piece_identite')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_statut_attribution')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_publication')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_traitement')
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
            'index' => Pages\ListAttributions::route('/'),
            'create' => Pages\CreateAttribution::route('/create'),
            'edit' => Pages\EditAttribution::route('/{record}/edit'),
        ];
    }
}
