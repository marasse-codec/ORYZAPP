<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BeneficiairesTypeCultureResource\Pages;
use App\Filament\Admin\Resources\BeneficiairesTypeCultureResource\RelationManagers;
use App\Models\BeneficiairesTypeCulture;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BeneficiairesTypeCultureResource extends Resource
{
    protected static ?string $model = BeneficiairesTypeCulture::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListBeneficiairesTypeCultures::route('/'),
            'create' => Pages\CreateBeneficiairesTypeCulture::route('/create'),
            'edit' => Pages\EditBeneficiairesTypeCulture::route('/{record}/edit'),
        ];
    }
}
