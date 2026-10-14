{{-- Superficies que la commission peut traiter, par écologie de la demande (état Alpine : ecologies, onSuperficieChange). --}}
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-base font-semibold text-[--color-primary]">Superficies traitables</h2>
    <p class="mb-4 text-xs text-[--color-text-secondary]">
        Par écologie. Les quantités des appuis liés à une écologie se recalculent quand la superficie change.
    </p>

    @if (empty($ecologies))
        <p class="text-sm italic text-slate-500">Aucune superficie n'a été déclarée sur cette demande.</p>
    @else
        <div class="grid gap-3 sm:grid-cols-3">
            <template x-for="ecologie in ecologies" :key="ecologie.id_type">
                <div class="rounded-lg border border-slate-200 p-3">
                    <label class="block text-xs font-medium text-[--color-text-secondary]" x-text="ecologie.libelle"></label>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="0" step="0.01" :max="ecologie.demandee"
                               :name="`superficies[${ecologie.id_type}]`"
                               x-model="ecologie.saisie"
                               @input="onSuperficieChange()"
                               class="block w-full rounded-md border-gray-300 text-sm focus:border-[--color-primary] focus:ring-[--color-primary]">
                        <span class="text-xs text-slate-500">ha</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Demandé : <span class="font-medium" x-text="Number(ecologie.demandee).toLocaleString('fr-FR')"></span> ha
                    </p>
                </div>
            </template>
        </div>
    @endif
</div>
