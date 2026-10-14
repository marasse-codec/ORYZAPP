@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Ouvrir une séance')

@section('content')
<x-ui.page titre="Ouvrir une séance de commission" sous-titre="La séance s'ouvre à la date du jour." class="max-w-2xl">
    <x-ui.card>
        <form action="{{ route('commissions.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="resume" value="Résumé de la séance (facultatif)" />
                <textarea id="resume" name="resume" rows="4" class="mt-1 block w-full" placeholder="Ordre du jour, membres présents…">{{ old('resume') }}</textarea>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-ui.bouton :href="route('commissions.index')" variante="secondaire">Annuler</x-ui.bouton>
                <x-ui.bouton type="submit">Ouvrir la séance</x-ui.bouton>
            </div>
        </form>
    </x-ui.card>
</x-ui.page>
@endsection
