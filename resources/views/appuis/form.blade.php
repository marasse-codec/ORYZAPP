@extends($layout ?? 'layouts.aderiz')

@php($modification = $appui->exists)

@section('titre-page', $modification ? 'Modifier un appui' : 'Nouvel appui')

@section('content')
<x-ui.page :titre="$modification ? 'Modifier « '.$appui->libelle.' »' : 'Nouvel appui'" sous-titre="La quantité en stock n'est pas modifiable ici." class="max-w-3xl">
    <x-ui.card>
        <form method="POST" action="{{ $modification ? route('appuis.update', $appui) : route('appuis.store') }}" class="space-y-5">
            @csrf
            @if ($modification) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="libelle" value="Libellé *" />
                    <x-text-input id="libelle" name="libelle" type="text" class="mt-1 block w-full" :value="old('libelle', $appui->libelle)" required />
                    <x-input-error :messages="$errors->get('libelle')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="id_type_appui" value="Type d'appui *" />
                    <select id="id_type_appui" name="id_type_appui" class="mt-1 block w-full" required>
                        <option value="">Choisir…</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id_type_appui }}" @selected((string) old('id_type_appui', $appui->id_type_appui) === (string) $type->id_type_appui)>{{ $type->lib_type_appui }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('id_type_appui')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="id_unite" value="Unité de dosage *" />
                    <select id="id_unite" name="id_unite" class="mt-1 block w-full" required>
                        <option value="">Choisir…</option>
                        @foreach ($unites as $unite)
                            <option value="{{ $unite->id_unite }}" @selected((string) old('id_unite', $appui->id_unite) === (string) $unite->id_unite)>{{ $unite->lib_unite }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('id_unite')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="dose_ha" value="Dose par hectare *" />
                    <x-text-input id="dose_ha" name="dose_ha" type="number" step="0.001" min="0" class="mt-1 block w-full" :value="old('dose_ha', $appui->dose_ha)" required />
                    <x-input-error :messages="$errors->get('dose_ha')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="cout_unitaire" value="Coût unitaire (F CFA)" />
                    <x-text-input id="cout_unitaire" name="cout_unitaire" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('cout_unitaire', $appui->cout_unitaire)" />
                    <x-input-error :messages="$errors->get('cout_unitaire')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="seuil_alerte" value="Seuil d'alerte de stock" />
                    <x-text-input id="seuil_alerte" name="seuil_alerte" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('seuil_alerte', $appui->seuil_alerte)" />
                    <p class="mt-1 text-xs text-[--color-muted]">En dessous, l'appui remonte dans les alertes. 0 = pas d'alerte.</p>
                    <x-input-error :messages="$errors->get('seuil_alerte')" class="mt-1" />
                </div>
            </div>

            @if ($systemes->isNotEmpty())
                <fieldset>
                    <legend class="text-sm font-medium text-slate-700">Systèmes de production concernés</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($systemes as $systeme)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="systemes[]" value="{{ $systeme->id_type_systeme_production }}" @checked(in_array($systeme->id_type_systeme_production, array_map('intval', (array) $systemesChoisis), true))>
                                {{ $systeme->lib_type_systeme_production }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('systemes')" class="mt-1" />
                </fieldset>
            @endif

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-ui.bouton :href="route('appuis.index')" variante="secondaire">Annuler</x-ui.bouton>
                <x-ui.bouton type="submit">{{ $modification ? 'Enregistrer' : "Ajouter l'appui" }}</x-ui.bouton>
            </div>
        </form>
    </x-ui.card>
</x-ui.page>
@endsection
