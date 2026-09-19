<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SessionCommissionResource\Pages;
use App\Filament\Admin\Resources\SessionCommissionResource\RelationManagers;
use App\Models\SessionCommission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SessionCommissionResource extends Resource
{
    protected static ?string $model = SessionCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('resume')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('date_debut_session')
                    ->required(),
                Forms\Components\DatePicker::make('date_fin_session'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_debut_session')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_fin_session')
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
            'index' => Pages\ListSessionCommissions::route('/'),
            'create' => Pages\CreateSessionCommission::route('/create'),
            'edit' => Pages\EditSessionCommission::route('/{record}/edit'),
        ];
    }
}
