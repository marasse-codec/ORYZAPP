<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-xl font-bold text-emerald-800 mb-4">Assistant Décisionnel IA (Text-to-SQL)</h2>

    <!-- Formulaire de question -->
    <form wire:submit.prevent="processQuestion" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Posez votre question à la base de données :</label>
            <div class="flex gap-2">
                <input 
                    type="text" 
                    wire:model="question" 
                    placeholder="Ex: Quel est le volume d'engrais NPK attribué dans le système irrigué ce trimestre ?"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                >
                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-emerald-700 text-white rounded-md text-sm font-semibold hover:bg-emerald-800 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="processQuestion">Interroger</span>
                    <span wire:loading wire:target="processQuestion">Analyse...</span>
                </button>
            </div>
            @error('question') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
    </form>

    <!-- Message d'erreur -->
    @if ($errorMessage)
        <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Affichage des résultats -->
    @if ($response)
        <div class="mt-6 space-y-4">
            <!-- Synthèse rédigée -->
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 rounded-r-md">
                <h3 class="font-bold text-sm text-emerald-800 mb-1">Synthèse IA :</h3>
                <p class="text-sm leading-relaxed">{{ $response['summary'] }}</p>
            </div>

            <!-- Tableau des résultats -->
            @if (count($response['data']) > 0)
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach (array_keys((array) $response['data'][0]) as $column)
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $column) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($response['data'] as $row)
                                <tr>
                                    @foreach ((array) $row as $value)
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                            {{ is_numeric($value) ? number_format($value, 0, ',', ' ') : $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 italic">Aucune donnée trouvée pour cette requête.</p>
            @endif

            <!-- Accordéon SQL généré (pour audit/vérification) -->
            <details class="text-xs text-gray-500 border-t pt-2">
                <summary class="cursor-pointer font-mono hover:text-gray-700">Voir la requête SQL exécutée</summary>
                <pre class="mt-2 p-3 bg-gray-900 text-green-400 rounded overflow-x-auto font-mono text-xs">{{ $response['sql'] }}</pre>
            </details>
        </div>
    @endif
</div>