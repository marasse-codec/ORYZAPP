@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-secondary] px-6 py-8">
    <div class="max-w-5xl mx-auto">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[--text-over-back-color]">Tableau de bord</h1>
            <p class="text-sm text-slate-500">{{ $total }} demande(s) dans votre périmètre</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($repartition as $ligne)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $ligne['statut']->couleur() }}">
                        {{ $ligne['statut']->libelle() }}
                    </span>
                    <p class="mt-3 text-3xl font-bold text-slate-800">{{ $ligne['total'] }}</p>
                </div>
            @endforeach
        </div>

            <!-- Section Graphiques & Analyses -->
        <div class="grid grid-cols-1 py-5 lg:grid-cols-1 gap-6">
            <livewire:ai-assistant />
            {{-- <livewire:taux-satisfactions-widget />
            <livewire:analyse-rejets-widget />
            <livewire:point-stocks-widget />
            <livewire:repartition-beneficiaires-widget />
            <livewire:repartition-regionale-widget /> --}}
        </div>

        <div class="mt-8">
            <a href="{{ route('demandes.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-[--color-primary] hover:underline">
                Voir toutes les demandes →
            </a>
        </div>
    </div>
</div>
@endsection
