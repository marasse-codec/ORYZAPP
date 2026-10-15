<x-filament-panels::page>
    <form wire:submit="enregistrer" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" icon="heroicon-o-check">Enregistrer</x-filament::button>
        </div>
    </form>

    <p class="text-sm text-gray-500">
        Chaque modification est notée au journal des actions, avec l'ancienne et la nouvelle valeur.
        La politique de mot de passe s'applique aux mots de passe saisis à partir de maintenant.
    </p>
</x-filament-panels::page>
