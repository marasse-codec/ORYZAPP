@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-6xl mx-auto">

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Demandes à instruire</h1>
                <p class="text-sm text-slate-500">{{ $service->lib_service }} — {{ $demandes->count() }} dossier(s) sollicitant vos appuis</p>
            </div>

            @if ($demandes->isNotEmpty())
                <form method="POST" action="{{ route('instructions.prendre-en-charge-tous') }}">
                    @csrf
                    <input type="hidden" name="service_id" value="{{ $service->id_service }}">
                    <button type="submit" 
                            onclick="return confirm('Voulez-vous vraiment prendre en charge tous les dossiers affichés ?')"
                            class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 shadow-sm transition">
                        <span class="material-symbols-outlined text-base">done_all</span>
                        Prendre en charge tous les dossiers
                    </button>
                </form>
            @endif
        </div>

        @include('partials.flash')

        @if ($servicesDisponibles->isNotEmpty())
            {{-- L'administrateur n'a pas de service propre : il choisit celui
                 dont il consulte les dossiers. --}}
            <form method="GET" action="{{ route('instructions.index') }}"
                  class="mb-5 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="service" class="block text-sm font-medium text-slate-700">Instruire au titre du service</label>
                    <select id="service" name="service"
                            class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm text-sm">
                        @foreach ($servicesDisponibles as $option)
                            <option value="{{ $option->id_service }}" @selected($option->id_service === $service->id_service)>
                                {{ $option->lib_service }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Afficher
                </button>
            </form>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Dossier</th>
                        <th class="px-4 py-3">Demandeur</th>
                        <th class="px-4 py-3">Localité</th>
                        <th class="px-4 py-3 text-center">Vos appuis</th>
                        <th class="px-4 py-3 text-center">Votre avis</th>
                        <th class="px-4 py-3 text-center">Dossier</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($demandes as $demande)
                        @php
                            $rapport = $demande->rapports->firstWhere('id_service', $service->id_service);
                            $nbLignes = $demande->lignes->filter(
                                fn ($l) => $l->appui?->typeAppui?->id_service === $service->id_service
                            )->count();
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $demande->acteur?->nom }}
                                <span class="block text-xs text-slate-500">{{ $demande->typeActeur?->lib_type_acteur }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $demande->localite?->lib_localite }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $nbLignes }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($rapport?->estAccorde())
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Accordé</span>
                                @elseif ($rapport)
                                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800">Observé</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">À traiter</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $demande->id_statut_demande->couleur() }}">
                                    {{ $demande->id_statut_demande->libelle() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('instructions.fiche', $demande->id_demande) }}" target="_blank"
                                       title="Fiche d'instruction à imprimer"
                                       class="inline-flex items-center rounded-md border border-slate-300 px-2 py-1.5 text-slate-600 hover:border-[--color-primary] hover:text-[--color-primary]">
                                        <span class="material-symbols-outlined text-sm">print</span>
                                    </a>
                                    <a href="{{ route('instructions.show', $demande->id_demande) }}"
                                       class="rounded-md bg-[--color-primary] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">
                                        Instruire
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Aucun dossier à instruire.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection