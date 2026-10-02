@props(['demande' => null])

<section class="p-6 space-y-6">

    <div>
        <h2 class="text-lg font-semibold text-[--color-primary]">Visite de terrain & Observations</h2>
        <p class="text-sm text-slate-500">Renseignez le statut de la visite de terrain et vos observations pour cette demande.</p>
    </div>

    {{-- Case à cocher : Indique si le terrain a été visité --}}
    <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" 
                   name="terrain_visite" 
                   value="1" 
                   {{ old('terrain_visite', $demande?->terrain_visite ?? false) ? 'checked' : '' }}
                   class="mt-1 rounded border-slate-300 text-[--color-primary] focus:ring-[--color-primary]">
            <div>
                <span class="block text-sm font-semibold text-slate-800">Le terrain a été visité</span>
                <span class="block text-xs text-slate-500">Cochez cette case si un agent de l'ADERIZ a réalisé une visite d'évaluation sur le terrain.</span>
            </div>
        </label>
        <x-input-error :messages="$errors->get('terrain_visite')" class="mt-1" />
    </div>

    {{-- Champ texte : Observations --}}
    <div>
        <label for="observations" class="block text-sm font-medium text-slate-700">
            Observations & Recommandations
        </label>
        <textarea id="observations" 
                  name="observations" 
                  rows="5" 
                  placeholder="Saisissez ici les observations de la visite ou les remarques relatives à la demande..."
                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-[--color-primary] focus:ring-[--color-primary] text-sm">{{ old('observations', $demande?->observations ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('observations')" class="mt-1" />
    </div>

</section>