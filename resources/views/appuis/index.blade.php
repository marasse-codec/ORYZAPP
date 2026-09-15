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
