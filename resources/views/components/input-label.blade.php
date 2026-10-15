@props(['value', 'obligatoire' => false])

{{-- Un libellé de champ. « obligatoire » ajoute l'astérisque rouge des champs à remplir. --}}
<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {{ $value ?? $slot }}@if ($obligatoire)<span class="ml-0.5 text-red-600" aria-hidden="true">*</span><span class="sr-only"> (obligatoire)</span>@endif
</label>
