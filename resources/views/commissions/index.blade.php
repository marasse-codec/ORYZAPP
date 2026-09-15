@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-4xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[--color-primary]">Sessions de commission</h1>
                <p class="text-sm text-slate-500">Une séance ouverte accueille les attributions ; sa clôture rend les bons servables.</p>
            </div>
            <a href="{{ route('commissions.create') }}"
               class="inline-flex items-center gap-1.5 rounded-md bg-[--color-primary] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                <span class="material-symbols-outlined text-base">add</span> Ouvrir une session
            </a>
        </div>

        @include('partials.flash')

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Séance</th>
                        <th class="px-4 py-3 text-center">Attributions</th>
                        <th class="px-4 py-3 text-center">État</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sessions as $session)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                Séance du {{ $session->date_debut_session?->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $session->attributions_count }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($session->estOuverte())
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Ouverte</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        Clôturée le {{ $session->date_fin_session?->format('d/m/Y') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('commissions.show', $session->id_session) }}"
                                   class="text-sm font-medium text-[--color-primary] hover:underline">Ouvrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Aucune séance enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
