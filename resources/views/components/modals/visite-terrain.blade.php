@props([
    'id',                       // ID unique pour gérer l'ouverture si plusieurs boutons sont sur la même page
    'action',                   // URL de la route (ex: route('demandes.visite.valider', $demande->id_demande))
    'title' => 'Confirmation de la visite du terrain'
])

<div x-data="{ open: false }" 
     @open-modal-visite-{{ $id }}.window="open = true" 
     @close-modal-visite-{{ $id }}.window="open = false" 
     class="inline">

    {{-- Déclencheur (Slot personnalisable ou bouton par défaut) --}}
    @if(isset($trigger))
        <div @click="open = true">
            {{ $trigger }}
        </div>
    @else
        <button type="button" 
                @click="open = true"
                class="text-xs font-semibold text-emerald-600 hover:underline">
            Valider la visite
        </button>
    @endif

    {{-- Structure de la Modal --}}
    <template x-teleport="body">
        <div x-show="open" 
             x-cloak
             @keydown.escape.window="open = false"
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div @click.away="open = false" 
                 class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 text-left space-y-4 border border-slate-200">
                
                {{-- En-tête --}}
                <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                    <h3 class="text-base font-bold text-slate-800">
                        {{ $title }}
                    </h3>
                    <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 text-sm">
                        ✕
                    </button>
                </div>

                {{-- Formulaire --}}
                <form action="{{ $action }}" method="POST" class="space-y-4">
                    @csrf

                    <input hidden name="id_demande" value="{{$id}}" >

                    <div>
                        <label for="observation_visite_{{ $id }}" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Observations / Rapport de visite <span class="text-red-500">*</span>
                        </label>
                        <textarea id="observation_visite_{{ $id }}" 
                                  name="observations" 
                                  rows="4" 
                                  required 
                                  placeholder="Saisissez les conclusions ou remarques suite à la visite sur le terrain..."
                                  class="w-full text-sm border-gray-300 rounded-lg focus:border-[--color-primary] focus:ring-[--color-primary] shadow-sm"></textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" 
                                @click="open = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-md border border-slate-300">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-xs font-semibold text-white bg-[--color-primary] hover:opacity-90 rounded-md shadow-sm">
                            Confirmer la visite
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>
</div>