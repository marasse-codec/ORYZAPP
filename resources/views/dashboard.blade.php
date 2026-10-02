@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-secondary] px-6 py-8">
    <div class="max-w-5xl mx-auto space-y-8">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[--text-over-back-color]">Tableau de bord</h1>
                <p class="text-sm text-slate-500">{{ array_sum($etats) }} demande(s) dans votre périmètre</p>
            </div>
            @if ($peutCreer)
                <a href="{{ route('demandes.create') }}"
                   class="rounded-lg bg-[--color-primary] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                    Nouvelle demande
                </a>
            @endif
        </div>

        {{-- Demandes par état : tous les rôles --}}
        <section>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (\App\Services\StatistiqueService::ETATS as $cle => $libelle)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">{{ $libelle }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $etats[$cle] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-base font-semibold text-slate-700">Répartition des demandes</h2>
                <div class="h-64"><canvas id="graphiqueEtats"></canvas></div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-base font-semibold text-slate-700">Demandes soumises par campagne</h2>
                @if ($parCampagne->isEmpty())
                    <p class="text-sm text-slate-500">Aucune demande soumise.</p>
                @else
                    <div class="h-64"><canvas id="graphiqueCampagnes"></canvas></div>
                @endif
            </div>
        </section>

        @includeWhen($commission, 'dashboard.partials.commission', ['commission' => $commission])
        @includeWhen($stock, 'dashboard.partials.stock', ['stock' => $stock])

        {{-- Dernières attributions visibles par le rôle (DP : publiées uniquement) --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-700">Dernières attributions</h2>
                <a href="{{ route('attributions.index') }}" class="text-sm font-medium text-[--color-primary] hover:underline">Tout voir →</a>
            </div>
            @forelse ($attributions as $attribution)
                <a href="{{ route('attributions.show', $attribution->id_attribution) }}"
                   class="flex items-center justify-between border-t border-slate-100 py-2 text-sm hover:bg-slate-50">
                    <span class="text-slate-700">{{ $attribution->demande?->acteur?->nom ?? 'Attribution directe' }}</span>
                    <span class="text-slate-500">
                        {{ $attribution->estPubliee() ? 'Publiée le '.$attribution->date_publication->format('d/m/Y') : 'Non publiée' }}
                    </span>
                </a>
            @empty
                <p class="text-sm text-slate-500">Aucune attribution pour le moment.</p>
            @endforelse
        </section>

        {{-- Assistant IA : réservé à l'admin (il interroge toute la base) --}}
        @if ($estAdmin)
            <livewire:ai-assistant />
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const couleurs = ['#94a3b8', '#f59e0b', '#047857', '#e11d48'];

        new Chart(document.getElementById('graphiqueEtats'), {
            type: 'doughnut',
            data: {
                labels: @json(array_values(\App\Services\StatistiqueService::ETATS)),
                datasets: [{ data: @json(array_values($etats)), backgroundColor: couleurs }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        const campagnes = document.getElementById('graphiqueCampagnes');
        if (campagnes) {
            new Chart(campagnes, {
                type: 'bar',
                data: {
                    labels: @json($parCampagne->keys()),
                    datasets: [{ label: 'Demandes', data: @json($parCampagne->values()), backgroundColor: '#047857' }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }
    </script>
@endpush
