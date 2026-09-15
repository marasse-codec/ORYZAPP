@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-2xl mx-auto">

        <h1 class="mb-6 text-2xl font-bold text-[--color-primary]">
            Nouvel appui
        </h1>

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <ul class="list-inside list-disc space-y-0.5Sélectionner une unité text-sm text-red-700">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('appuis.store') }}"
            method="POST"
            x-data="{
                estEquipement: '{{ old('est_equipement', '0') }}' === '1'
            }"
            class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf

            {{-- Libellé --}}
            <div>
                <x-input-label
                    for="libelle"
                    value="Libellé de l'appui"
                />

                <x-text-input
                    id="libelle"
                    name="libelle"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('libelle')"
                    placeholder="Ex. Ordinateur portable Dell"
                    required
                />
            </div>


            {{-- Type d'appui --}}
            <div>
                <x-input-label
                    for="id_type_appui"
                    value="Type d'appui"
                />

                <select
                    id="id_type_appui"
                    name="id_type_appui"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300
                           shadow-sm focus:border-[--color-primary]
                           focus:ring-[--color-primary]"
                >
                    <option value="">— Sélectionner —</option>

                    @foreach ($typesAppuis as $type)
                        <option
                            value="{{ $type->id_type_appui }}"
                            @selected(
                                old('id_type_appui') == $type->id_type_appui
                            )
                        >
                            {{ $type->libelle }}
                            —
                            {{ $type->service?->lib_service }}
                        </option>
                    @endforeach
                </select>

                <x-input-error
                    :messages="$errors->get('id_type_appui')"
                    class="mt-1"
                />
            </div>


            {{-- Nature de l'appui --}}
            <div>
                <x-input-label
                    for="est_equipement"
                    value="Nature de l'appui"
                />

                <select
                    id="est_equipement"
                    name="est_equipement"
                    x-model="estEquipement"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300
                           shadow-sm focus:border-[--color-primary]
                           focus:ring-[--color-primary]"
                >
                    <option value="0">
                        Consommable
                    </option>

                    <option value="1">
                        Équipement
                    </option>
                </select>

                <p class="mt-1 text-xs text-slate-500">
                    Un équipement est géré individuellement
                    (matricule, état, affectation), contrairement
                    à un consommable.
                </p>

                <x-input-error
                    :messages="$errors->get('est_equipement')"
                    class="mt-1"
                />
            </div>

            <div>
                <x-input-label
                    for="id_unite_appui"
                    value="Unité"
                />

                <select
                    id="id_unite_appui"
                    name="id_unite_appui"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300
                        shadow-sm focus:border-[--color-primary]
                        focus:ring-[--color-primary]"
                >
                    <option value=""> Sélectionner une unité </option>

                    @foreach ($unitesAppui as $unite)
                        <option
                            value="{{ $unite->id_unite_appui }}"
                            @selected(
                                old('id_unite_appui') == $unite->id_unite_appui
                            )
                        >
                            {{ $unite->lib_unite_appui }}
                        </option>
                    @endforeach
                </select>

                <x-input-error
                    :messages="$errors->get('id_unite_appui')"
                    class="mt-1"
                />
            </div>


            {{-- Informations de stock --}}
            <div class="grid gap-4 sm:grid-cols-2">

                {{-- Quantité --}}
                <div>
                    <x-input-label
                        for="quantite_en_stock"
                        value="Quantité en stock"
                    />

                    <x-text-input
                        id="quantite_en_stock"
                        name="quantite_en_stock"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                        :value="old('quantite_en_stock', 0)"
                        :disabled="old('est_equipement', '0') == '1'"
                        x-bind:required="!estEquipement"
                    />

                    <p
                        x-show="estEquipement"
                        x-cloak
                        class="mt-1 text-xs text-slate-500"
                    >
                        La quantité sera gérée à travers les équipements
                        enregistrés individuellement.
                    </p>

                    <x-input-error
                        :messages="$errors->get('quantite_en_stock')"
                        class="mt-1"
                    />
                </div>


                {{-- Coût unitaire --}}
                <div>
                    <x-input-label
                        for="cout_unitaire"
                        value="Coût unitaire (F CFA)"
                    />

                    <x-text-input
                        id="cout_unitaire"
                        name="cout_unitaire"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full"
                        :value="old('cout_unitaire')"
                    />

                    <x-input-error
                        :messages="$errors->get('cout_unitaire')"
                        class="mt-1"
                    />
                </div>

            </div>


            {{-- Actions --}}
            <div class="flex justify-end gap-3">

                <a
                    href="{{ route('appuis.index') }}"
                    class="rounded-md border border-slate-300 px-4 py-2
                           text-sm text-slate-700 hover:bg-slate-50"
                >
                    Annuler
                </a>

                <button
                    type="submit"
                    class="rounded-md bg-[--color-primary] px-5 py-2
                           text-sm font-semibold text-white hover:opacity-90"
                >
                    Enregistrer
                </button>

            </div>

        </form>

    </div>
</div>
@endsection
