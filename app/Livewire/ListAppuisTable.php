<?php

namespace App\Livewire;

use App\Models\Appui;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;

class ListAppuisTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Schéma réutilisable pour les formulaires de création et modification
     */
    protected function getAppuiFormSchema(): array
    {
        return [
            // Conteneur de grilles personnalisé avec un fond légèrement teinté
            Forms\Components\Section::make()
                ->schema([
                    // Sélection du Type d'Appui
                    Forms\Components\Select::make('id_type_appui')
                        ->relationship(
                            name: 'typeAppui',
                            titleAttribute: 'lib_type_appui'
                        )
                        ->label("Type d'appui")
                        ->searchable()
                        ->preload()
                        ->required(),

                    // Libellé
                    Forms\Components\TextInput::make('libelle')
                        ->label('Libellé / Nom de l\'appui')
                        ->required()
                        ->maxLength(255),

                    // Coût unitaire
                    Forms\Components\Textarea::make('cout_unitaire')
                        ->label('Coût unitaire')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->extraAttributes([
                    // Permet de styliser la section hôte
                    'class' => 'bg-slate-50 border border-slate-200 rounded-xl p-4',
                ]),
            ];

    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Appui::query()->with('typeAppui'))
            ->columns([
                Tables\Columns\TextColumn::make('typeAppui.lib_type_appui')
                    ->label("Type d'appui")
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('libelle')
                    ->label('Libellé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cout_unitaire')
                    ->label('Coût unitaire')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_appui_id')
                    ->relationship('typeAppui', 'lib_type_appui')
                    ->label("Filtrer par type"),
            ])
            ->headerActions([
                // Bouton de Création stylisé
                Tables\Actions\CreateAction::make()
                    ->label('Nouvel appui')
                    ->modalHeading('Créer un nouvel appui ADERIZ')
                    ->modalWidth('2xl')
                    ->form($this->getAppuiFormSchema()),
            ])
            ->actions([
                // Modale d'Édition
                Tables\Actions\EditAction::make()
                    ->modalHeading('Modifier l\'appui')
                    ->modalWidth('2xl')
                    ->form($this->getAppuiFormSchema()),

                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public function render()
    {
        return view('livewire.list-appuis-table');
    }
}