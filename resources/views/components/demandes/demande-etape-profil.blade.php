@props(['typesActeurs', 'demande' => null])

@php
    // Collection des pièces déjà enregistrées pour cette demande
    $piecesExistantes =$demande?->piecesDemandes->pluck('id_type_piece')->toArray() ?? [];
@endphp

<section x-show="step === 1" x-cloak class="p-6 space-y-5" 
         x-data="{ piecesExistantes: {{ Illuminate\Support\Js::from($piecesExistantes) }} }">
    <div>
        <h2 class="text-lg font-semibold text-[--color-primary]">1. Profil du demandeur</h2>
        <p class="text-sm text-slate-500">Ce choix détermine les pièces justificatives à fournir.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($typesActeurs as $typeActeur)
            <label class="flex items-start gap-3 rounded-lg border p-4 cursor-pointer transition-colors"
                   :class="idTypeActeur == '{{ $typeActeur->id_type_acteur }}'
                       ? 'border-[--color-primary] bg-emerald-50'
                       : 'border-slate-200 hover:border-slate-300'">
                <input type="radio" name="id_type_acteur" value="{{ $typeActeur->id_type_acteur }}"
                       x-model="idTypeActeur" class="mt-1 text-[--color-primary] focus:ring-[--color-primary]">
                <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $typeActeur->lib_type_acteur }}</span>
                    <span class="block text-xs text-slate-500">
                        {{ $typeActeur->typesPieces->count() }} pièce(s) à fournir
                    </span>
                </span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('id_type_acteur')" class="mt-1" />
    
    <div>
        <h3 class="text-base font-semibold text-[--color-primary]">Pièces justificatives</h3>
        <p class="text-sm text-slate-500" x-show="!idTypeActeur" x-cloak>
            Sélectionnez d'abord un profil ci-dessus.
        </p>
        <p class="text-sm text-slate-500" x-show="idTypeActeur && piecesAttendues.length === 0" x-cloak>
            Aucune pièce n'est exigée pour ce profil.
        </p>

        <div class="mt-3 space-y-4">
            <template x-for="piece in piecesAttendues" :key="piece.id">
                <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            <span x-text="piece.libelle"></span>
                            <span class="text-red-600" x-show="piece.obligatoire && !piecesExistantes.includes(piece.id)">*</span>
                        </label>

                        {{-- Indicateur si pièce déjà présente en BDD --}}
                        <template x-if="piecesExistantes.includes(piece.id)">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                ✓ Pièce déjà fournie
                            </span>
                        </template>
                    </div>

                    <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                           :name="`pieces[${piece.id}]`" 
                           :required="piece.obligatoire && !piecesExistantes.includes(piece.id)"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-[--color-primary] file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:opacity-90">
                    
                    <p x-show="piecesExistantes.includes(piece.id)" class="text-xs text-slate-400 mt-1">
                        Laissez vide pour conserver la pièce actuelle.
                    </p>
                </div>
            </template>
        </div>
        <x-input-error :messages="$errors->get('pieces')" class="mt-1" />
    </div>
</section>