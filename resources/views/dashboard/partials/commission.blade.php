{{-- Commission : ce qui attend son arbitrage ou sa publication --}}
<section>
    <h2 class="mb-3 text-base font-semibold text-slate-700">Commission</h2>
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('commissions.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
            <p class="text-sm text-slate-500">Sessions ouvertes</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $commission['sessions_ouvertes'] }}</p>
        </a>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Demandes à arbitrer</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $commission['demandes_a_arbitrer'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Attributions à publier</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $commission['attributions_a_publier'] }}</p>
        </div>
    </div>
</section>
