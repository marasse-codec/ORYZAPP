@extends($layout ?? 'layouts.aderiz')

@section('content')
<div class="min-h-screen bg-[--color-background] px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="mb-2 text-2xl font-bold text-[--color-primary]">Ouvrir une session de commission</h1>
        <p class="mb-6 text-sm text-slate-500">La séance s'ouvre à la date du jour. Une seule séance par jour.</p>

        @include('partials.flash')

        <form action="{{ route('commissions.store') }}" method="POST"
              class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <x-input-label for="resume" value="Résumé de la séance (facultatif)" />
                <textarea id="resume" name="resume" rows="4"
                          class="mt-1 block w-full border-gray-300 focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm"
                          placeholder="Ordre du jour, membres présents…">{{ old('resume') }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('commissions.index') }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Annuler</a>
                <button type="submit"
                        class="rounded-md bg-[--color-primary] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Ouvrir la séance
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
