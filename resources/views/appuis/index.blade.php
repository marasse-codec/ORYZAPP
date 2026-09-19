@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-5xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Catalogue des appuis</h1>
                <p class="text-sm text-slate-500">{{ $appuis->count() }} appui(s) référencé(s)</p>
            </div>
            <a href="{{ route('appuis.create') }}"
               class="inline-flex items-center gap-1.5 rounded-md bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                <span class="material-symbols-outlined text-base">add</span> Nouvel appui
            </a>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Appui</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Service</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-right">Coût unitaire</th>
                        <th class=> Sour </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($appuis as $appui)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $appui->libelle }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $appui->typeAppui?->libelle }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $appui->typeAppui?->service?->lib_service }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">
                                {{ $appui->quantite_en_stock }}
                                <span class="text-slate-500">{{ $appui->uniteAppui?->lib_unite_appui }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-800">
                                {{ $appui->cout_unitaire ? number_format($appui->cout_unitaire, 0, ',', ' ').' F' : '—' }}
                            </td>
                            <td>
                                <div x-data="{ 
                                    subscribed: {{ $appui->isSubscribedBy(auth()->user()) ? 'true' : 'false' }},
                                    loading: false,
                                    async toggleAlert() {
                                        this.loading = true;
                                        try {
                                            let response = await fetch('{{ route('resources.toggle-alert', ['type' => 'appui', 'id' => $appui->id_appui]) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json',
                                                    'Content-Type': 'application/json'
                                                }
                                            });
                                            let data = await response.json();
                                            this.subscribed = data.subscribed;
                                        } catch (error) {
                                            console.error('Erreur lors du basculement de l\'alerte', error);
                                        } finally {
                                            this.loading = false;
                                        }
                                    }
                                }">
                                    <button @click="toggleAlert()" 
                                            :disabled="loading"
                                            :class="subscribed ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                            class="inline-flex items-center gap-2 px-3 py-2 border text-sm font-medium rounded-lg shadow-sm transition disabled:opacity-50">
                                        
                                        <svg class="h-4 w-4" :class="subscribed ? 'fill-amber-500 text-amber-500' : 'text-slate-400'" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>

                                        <span x-text="subscribed ? 'Alerte activée' : 'M\'alerter des changements'"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Aucun appui référencé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
