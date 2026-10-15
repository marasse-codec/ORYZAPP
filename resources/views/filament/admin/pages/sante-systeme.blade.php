<x-filament-panels::page>
    <x-filament::section heading="Contrôles" description="Recalculés à chaque affichage de la page.">
        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($verifications as $verification)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $verification->libelle }}</p>
                        <p class="text-sm text-gray-500">{{ $verification->detail }}</p>
                    </div>
                    <x-filament::badge :color="$verification->couleur()">{{ $verification->libelleEtat() }}</x-filament::badge>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
