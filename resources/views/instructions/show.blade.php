@extends($layout ?? 'layouts.aderiz')

@section('content')


<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-4xl mx-auto space-y-6">

        @if ($demande->estSoumise())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
        <h2 class="text-base font-semibold text-amber-900">
            Demander une correction
        </h2>

        <p class="mt-1 text-sm text-amber-800">
            Si des informations doivent être corrigées par le demandeur,
            indiquez précisément les éléments à modifier.
        </p>

        <form action="{{ route('instructions.demander-correction', $demande->id_demande) }}"
              method="POST"
              class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="motif_correction"
                       class="block text-sm font-medium text-slate-700">
                    Motif de correction
                </label>

                <textarea
                    id="motif_correction"
                    name="motif"
                    rows="4"
                    required
                    maxlength="300"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[--color-primary] focus:ring-[--color-primary]"
                    placeholder="Indiquez les informations que le demandeur doit corriger...">{{ old('motif') }}</textarea>

                @error('motif')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        onclick="return confirm('Voulez-vous réellement renvoyer cette demande pour correction ?')"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Remettre en correction
                </button>
            </div>
        </form>
    </div>
@endif


        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-[--color-primary]">
                        DEM-{{ $demande->date_demande?->format('Y') }}-{{ str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT) }}
                        — {{ $demande->acteur?->nom }}
                    </h1>
                    <p class="text-sm text-slate-500">Instruction au titre du {{ $service->lib_service }}</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- La fiche papier accompagne l'agent sur le terrain. --}}
                    <a href="{{ route('instructions.fiche', $demande->id_demande) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-md border border-[--color-primary] px-3 py-1.5 text-xs font-semibold text-[--color-primary] hover:bg-emerald-50">
                        <span class="material-symbols-outlined text-sm">print</span> Fiche d'instruction
                    </a>
                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $demande->id_statut_demande->couleur() }}">
                        {{ $demande->id_statut_demande->libelle() }}
                    </span>
                </div>
            </div>
        </div>

        @include('partials.flash')

        {{-- 1. Les appuis relevant du service --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">1. Appuis relevant de votre service</h2>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="pb-2">Appui</th>
                        <th class="pb-2 text-right">Quantité demandée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lignesDuService as $ligne)
                        @php $unite = $ligne->appui?->typeAppui?->uniteTypeAppui?->lib_unite_type_appui; @endphp
                        <tr>
                            <td class="py-2 text-slate-800">
                                {{ $ligne->appui?->libelle }}
                                <span class="block text-xs text-slate-500">{{ $ligne->appui?->typeAppui?->libelle }}</span>
                            </td>
                            <td class="py-2 text-right text-slate-800">{{ $ligne->quantite_demandee }} {{ $unite }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @php $autres = $demande->lignes->diff($lignesDuService); @endphp
            @if ($autres->isNotEmpty())
                <p class="mt-4 text-xs text-slate-500">
                    Le dossier sollicite également {{ $autres->count() }} appui(s) relevant d'autres services :
                    {{ $autres->map(fn ($l) => $l->appui?->typeAppui?->service?->lib_service)->unique()->filter()->join(', ') }}.
                </p>
            @endif
        </div>

        {{-- 2. Demandeur --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">2. Informations du demandeur</h2>
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Nom</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->acteur?->nom }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Type d'acteur</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->typeActeur?->lib_type_acteur }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Contact</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->acteur?->telephone }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">E-mail</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->acteur?->email ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- 3. La demande --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-[--color-primary]">3. Informations sur la demande</h2>
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Localité</dt>
                    <dd class="mt-0.5 text-slate-800">
                        {{ $demande->localite?->lib_localite }}
                        <span class="text-slate-500">({{ $demande->localite?->departement?->lib_departement }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Mise en place</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $demande->debut_periode_mise_en_place?->format('d/m/Y') }}</dd>
                </div>
            </dl>

            <table class="mt-4 min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="pb-2">Système de production</th>
                        <th class="pb-2 text-right">Superficie (ha)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($demande->systemesProduction as $systeme)
                        <tr>
                            <td class="py-2 text-slate-800">{{ $systeme->typeSystemeProduction?->lib_type_systeme_production }}</td>
                            <td class="py-2 text-right text-slate-800">{{ number_format($systeme->superficie, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-200 font-semibold">
                        <td class="pt-2 text-slate-700">Total</td>
                        <td class="pt-2 text-right text-slate-800">
                            {{ number_format($demande->systemesProduction->sum('superficie'), 2, ',', ' ') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- 4. Pièces --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-[--color-primary]">4. Pièces justificatives</h2>
                @if ($demande->piecesDemandes->isNotEmpty())
                    {{-- Les pièces accompagnent la fiche lors de la visite de terrain. --}}
                    <a href="{{ route('demandes.pieces', $demande->id_demande) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-md border border-[--color-primary] px-3 py-1.5 text-xs font-semibold text-[--color-primary] hover:bg-emerald-50">
                        <span class="material-symbols-outlined text-sm">print</span> Imprimer les pièces
                    </a>
                @endif
            </div>
            @if ($demande->piecesDemandes->isEmpty())
                <p class="text-sm text-slate-500">Aucune pièce jointe.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($demande->piecesDemandes as $piece)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <span class="text-slate-800">{{ $piece->typePiece?->lib_type_piece }}</span>
                            <a href="{{ $piece->url() }}" target="_blank" rel="noopener"
                               class="font-medium text-[--color-primary] hover:underline">Ouvrir</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Avis des autres services --}}
        @php $autresRapports = $demande->rapports->where('id_service', '!=', $service->id_service); @endphp
        @if ($autresRapports->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-[--color-primary]">Avis des autres services</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($autresRapports as $autre)
                        <li class="rounded-lg border border-slate-200 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">{{ $autre->service?->lib_service }}</span>
                                <span class="text-xs {{ $autre->estAccorde() ? 'text-emerald-700' : 'text-orange-700' }}">
                                    {{ $autre->estAccorde() ? 'Accordé le '.$autre->accord_service->format('d/m/Y') : 'En cours' }}
                                </span>
                            </div>
                            @if ($autre->commentaire)
                                <p class="mt-1 text-slate-600">{{ $autre->commentaire }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Formulaire d'instruction --}}
        <form action="{{ route('instructions.store', $demande->id_demande) }}" method="POST"
              class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <x-input-label for="commentaire" value="Observation" />
                <textarea id="commentaire" name="commentaire" rows="4"
                          class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm"
                          placeholder="Ex. : bonne pour commission.">{{ old('commentaire', $rapport?->commentaire) }}</textarea>
            </div>

            @if ($rapport?->estAccorde())
                <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Votre service a déjà autorisé ce dossier le {{ $rapport->accord_service->format('d/m/Y') }}.
                    Soumettre à nouveau sans cocher la case retirerait cette autorisation.
                </p>
            @endif

            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4 cursor-pointer">
                <input type="checkbox" name="accorder" value="1" @checked(old('accorder', $rapport?->estAccorde()))
                       class="mt-1 rounded border-gray-300 text-[--color-primary] focus:ring-[--color-primary]">
                <span>
                    <span class="block text-sm font-medium text-slate-800">Autoriser pour commission</span>
                    <span class="block text-xs text-slate-500">
                        Le dossier ne partira en commission qu'une fois tous les services concernés favorables.
                    </span>
                </span>
            </label>

            <div class="flex justify-end gap-3">
                <a href="{{ route('instructions.index') }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Enregistrer l'instruction
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
