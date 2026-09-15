<?php

namespace App\Livewire;

use App\Models\Equipement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EquipementsTable extends PowerGridComponent
{
    public string $tableName = 'equipements';

    // 1. Indiquer à PowerGrid d'utiliser id_equipement au lieu de 'id'
    public string $primaryKey = 'id_equipement';

    // 2. Définir le champ de tri par défaut (ex: id_equipement, matricule_equipement, etc.)
    public string $sortField = 'id_equipement';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Equipement::query()->with('appui');
    }

    public function relationSearch(): array
    {
        return [
            'appui' => [
                'libelle',
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id_equipement')
            ->add('matricule_equipement')
            ->add('appui_libelle', fn (Equipement $model) => $model->appui?->libelle ?? '—')
            /** Example of custom column using a closure **/
            ->add('matricule_equipement_lower', fn (Equipement $model) => strtolower(e($model->matricule_equipement)))

            ->add('etat')
            ->add('en_service_badge', fn (Equipement $model) => $model->en_service
                ? '<span class="px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-slate-600 font-medium">Remis</span>'
                : '<span class="px-2 py-0.5 text-xs rounded-full bg-slate-100 text-emerald-800 font-medium">En parking</span>')
            ->add('id_appui')
            ->add('created_at_formatted', fn (Equipement $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Id equipement', 'id_equipement'),
            Column::make('Matricule equipement', 'matricule_equipement')
                ->sortable()
                ->searchable(),

            Column::make('Etat', 'etat')
                ->sortable()
                ->searchable(),

            Column::make('En service', 'en_service_badge', 'en_service')
                ->sortable(),

            Column::make('Id appui', 'id_appui'),
            Column::make('Created at', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Action'),
        ];
    }

    public function filters(): array
    {
        // Déclarer explicitement la colonne ciblée en 2ème argument
        return [
            Filter::inputText('matricule_equipement', 'matricule_equipement')
                ->operators(['contains']),

            Filter::inputText('etat', 'etat')
                ->operators(['contains']),

            Filter::boolean('en_service', 'en_service')->label('Remis', 'En parking'),

            Filter::datetimepicker('created_at', 'created_at'),
        ];
    }

    #[On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert("Équipement sélectionné : " + '.$rowId.')');
    }

    public function actions(Equipement $row): array
    {
        return [
            Button::add('edit')
                ->slot('Modifier')
                ->class('bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1 px-2 rounded text-xs')
                ->dispatch('edit', ['rowId' => $row->id_equipement]),
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
