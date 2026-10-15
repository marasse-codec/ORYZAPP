<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Administration\Enums\ParametreEnum;
use App\Domain\Administration\Services\ParametreService;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Les paramètres généraux : l'administrateur règle lui-même la politique de mot
 * de passe, le blocage des connexions et les délais des rappels, sans toucher
 * au code. Le formulaire se construit à partir de ParametreEnum.
 */
class ParametresGeneraux extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Système';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Paramètres généraux';

    protected static ?string $title = 'Paramètres généraux';

    protected static ?string $slug = 'parametres';

    protected static string $view = 'filament.admin.pages.parametres-generaux';

    /** Les valeurs du formulaire, clé du paramètre => valeur. */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermissionEnum::gererRessources->value) ?? false;
    }

    public function mount(ParametreService $parametres): void
    {
        $this->form->fill($parametres->tous());
    }

    public function form(Form $form): Form
    {
        // Un encadré par groupe (Mots de passe, Connexion, Rappels), un champ par paramètre.
        $sections = collect(ParametreEnum::cases())
            ->groupBy(fn (ParametreEnum $p) => $p->groupe())
            ->map(fn ($parametres, string $groupe) => Forms\Components\Section::make($groupe)
                ->columns(2)
                ->schema($parametres->map(fn (ParametreEnum $p) => $this->champ($p))->all()))
            ->values()
            ->all();

        return $form->schema($sections)->statePath('data');
    }

    public function enregistrer(ParametreService $parametres): void
    {
        try {
            $parametres->modifier($this->form->getState());
            Notification::make()->title('Paramètres enregistrés')->success()->send();
        } catch (RegleMetierViolee $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /** Un interrupteur pour un oui/non, une case numérique bornée pour un nombre. */
    private function champ(ParametreEnum $parametre): Forms\Components\Field
    {
        if ($parametre->estOuiNon()) {
            return Forms\Components\Toggle::make($parametre->value)->label($parametre->libelle());
        }

        [$min, $max] = $parametre->bornes();

        return Forms\Components\TextInput::make($parametre->value)
            ->label($parametre->libelle())
            ->numeric()->integer()->minValue($min)->maxValue($max)->required()
            ->helperText("Entre {$min} et {$max}. Par défaut : {$parametre->defaut()}.");
    }
}
