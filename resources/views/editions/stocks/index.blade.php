@php
    use App\Domain\Suivi\Exports\StocksExport;

    $catalogueColonnes = StocksExport::catalogueColonnes();
@endphp
@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Stocks')

@section('content')
<div x-data="{ modalExport: false }" class="mx-auto max-w-6xl space-y-6">

    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-xl font-bold text-[--color-primary] sm:text-2xl">État des stocks</h1>
            <p class="text-sm text-slate-500">
                {{ $appuis->count() }} appui(s)
                @if ($service), service {{ $service->lib_service }} @endif
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        {{-- Un administrateur ou le magasin choisit le service ; l'agent de service a le sien. --}}
        @if ($services->isNotEmpty())
            <form method="GET" action="{{ route('editions.stocks.index') }}" class="flex items-center gap-2">
                <label for="id_service" class="text-sm text-slate-500">Service</label>
                <select id="id_service" name="id_service" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
                    <option value="">Tous les services</option>
                    @foreach ($services as $option)
                        <option value="{{ $option->id_service }}" @selected($idService === $option->id_service)>{{ $option->lib_service }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        {{-- Bouton déclencheur du modal d'exportation --}}
        <button type="button" @click="modalExport = true"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 font-semibold text-white shadow-sm transition-colors hover:bg-emerald-800">
            <span class="material-symbols-outlined text-lg">download_for_offline</span>
            Exporter les stocks (Excel)
        </button>
        </div>
    </div>

    {{-- Modal de configuration de l'exportation Excel --}}
    <div x-show="modalExport" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
         x-transition>
        <div class="w-full max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl"
             @click.outside="modalExport = false">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="flex items-center gap-2 text-lg font-bold text-[--color-primary]">
                    <span class="material-symbols-outlined text-emerald-600">tune</span>
                    Configurer l'exportation Excel
                </h3>
                <button type="button" @click="modalExport = false" class="text-lg text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('editions.stocks.export') }}" method="GET" class="space-y-6"
                  x-data="{
                      colonnes: {{ Illuminate\Support\Js::from(array_keys($catalogueColonnes)) }},
                      toutesColonnes: {{ Illuminate\Support\Js::from(array_keys($catalogueColonnes)) }},
                      toggleAll() {
                          this.colonnes = this.colonnes.length === this.toutesColonnes.length ? [] : [...this.toutesColonnes];
                      }
                  }">

                {{-- 1. Service : celui de l'aperçu (l'agent de service est de toute façon limité au sien) --}}
                @if ($services->isNotEmpty())
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Service</label>
                        <select name="id_service" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Tous les services</option>
                            @foreach ($services as $option)
                                <option value="{{ $option->id_service }}" @selected($idService === $option->id_service)>{{ $option->lib_service }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($service)
                    <p class="text-sm text-slate-600">Service : <span class="font-semibold">{{ $service->lib_service }}</span></p>
                @endif

                {{-- 2. Sélection des colonnes --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Colonnes à inclure</label>
                        <button type="button" @click="toggleAll()" class="text-xs font-semibold text-emerald-700 hover:underline">
                            <span x-text="colonnes.length === toutesColonnes.length ? 'Tout décocher' : 'Tout cocher'"></span>
                        </button>
                    </div>

                    <div class="grid max-h-60 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        @foreach ($catalogueColonnes as $cle => $libelle)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md p-1 text-sm text-slate-700 hover:bg-slate-100">
                                <input type="checkbox" name="colonnes[]" value="{{ $cle }}" x-model="colonnes"
                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span>{{ $libelle }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-3">
                    <button type="button" @click="modalExport = false"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Annuler
                    </button>
                    <button type="submit" @click="modalExport = false"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2 font-semibold text-white shadow-sm transition-colors hover:bg-emerald-800">
                        <span class="material-symbols-outlined text-base">download</span>
                        Télécharger l'export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="tableau-carte min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Appui</th>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Unité</th>
                    <th class="px-4 py-3 text-right">Stock</th>
                    <th class="px-4 py-3 text-right">Seuil d'alerte</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($appuis as $appui)
                    @php $stock = $appui->quantiteeEnStock(); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $appui->libelle }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $appui->typeAppui?->service?->lib_service }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $appui->uniteDosage?->lib_unite }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $appui->seuil_alerte > 0 && $stock <= $appui->seuil_alerte ? 'text-rose-600' : 'text-slate-800' }}">{{ $stock }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $appui->seuil_alerte }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Aucun appui pour ce service.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
