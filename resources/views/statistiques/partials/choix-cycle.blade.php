{{--
    Choix du cycle en deux temps : l'année, puis l'un de ses cycles.
    Changer d'année recharge la page sur cette année (son cycle en cours, à défaut le dernier).
    Paramètres : $annees, $annee, $cycles (cycles de l'année), $campagne (cycle affiché).
--}}
<label class="text-sm">
    <span class="text-[--color-muted]">Année</span>
    <select name="annee" class="mt-1 w-full rounded-lg border-slate-300 text-sm"
            onchange="this.form.querySelector('[name=campagne]').value = ''; this.form.submit()">
        @foreach ($annees as $option)
            <option value="{{ $option }}" @selected($option === $annee)>{{ $option }}</option>
        @endforeach
    </select>
</label>
<label class="text-sm">
    <span class="text-[--color-muted]">Cycle</span>
    <select name="campagne" class="mt-1 w-full rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
        @foreach ($cycles as $cycle)
            <option value="{{ $cycle->id_campagne }}" @selected($campagne && $cycle->is($campagne))>
                {{ $cycle->lib_campagne }} ({{ $cycle->mois_debut_campagne->format('m/Y') }} - {{ $cycle->mois_fin_campagne->format('m/Y') }}){{ $cycle->estCloturee() ? ' · clôturé' : '' }}
            </option>
        @endforeach
    </select>
</label>
