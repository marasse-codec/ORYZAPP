@props(['href', 'actif' => false, 'icone' => null])

{{-- Un lien du menu latéral ; sans icône, c'est un sous-lien de groupe. --}}
<a href="{{ $href }}" @if ($actif) aria-current="page" @endif
   {{ $attributes->class([
       'flex items-center gap-3 rounded-lg px-3 py-2 transition-colors',
       'bg-white/15 font-semibold text-white' => $actif,
       'text-emerald-50/90 hover:bg-white/10 hover:text-white' => ! $actif,
       'pl-10 text-[0.8125rem]' => ! $icone,
   ]) }}>
    @if ($icone)
        <span class="material-symbols-outlined text-xl">{{ $icone }}</span>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
