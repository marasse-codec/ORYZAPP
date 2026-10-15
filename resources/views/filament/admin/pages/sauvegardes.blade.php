<x-filament-panels::page>
    <x-filament::section heading="État">
        <dl class="grid gap-4 sm:grid-cols-4">
            <div>
                <dt class="text-sm text-gray-500">Santé</dt>
                <dd class="mt-1">
                    <x-filament::badge :color="$etat['saine'] ? 'success' : 'danger'">
                        {{ $etat['saine'] ? 'À jour' : ($etat['derniere'] ? 'Trop ancienne' : 'Aucune sauvegarde') }}
                    </x-filament::badge>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Dernière sauvegarde</dt>
                <dd class="mt-1 font-medium">{{ $etat['derniere']?->format('d/m/Y à H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Archives conservées</dt>
                <dd class="mt-1 font-medium">{{ $etat['nombre'] }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Espace occupé</dt>
                <dd class="mt-1 font-medium">{{ number_format($etat['taille'] / 1048576, 1, ',', ' ') }} Mo</dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="Archives" description="Sauvegarde automatique chaque nuit à 2 h. Les archives sont toutes conservées : aucune n'est supprimée automatiquement.">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2 pr-4">Archive</th>
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Taille</th>
                        <th class="py-2 pr-4">Disque</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($archives as $archive)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $archive['nom'] }}</td>
                            <td class="py-2 pr-4">{{ $archive['date']->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-4">{{ number_format($archive['taille'] / 1048576, 2, ',', ' ') }} Mo</td>
                            <td class="py-2 pr-4">{{ $archive['disque'] }}</td>
                            <td class="py-2 text-right">
                                <x-filament::link :href="route('sauvegardes.telecharger', ['disque' => $archive['disque'], 'nom' => $archive['nom']])" icon="heroicon-o-arrow-down-tray">
                                    Télécharger
                                </x-filament::link>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">Aucune archive pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
