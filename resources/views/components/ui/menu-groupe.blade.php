@props(['titre', 'icone' => null, 'ouvert' => false])

{{-- Un groupe de liens repliable ; il s'ouvre de lui-même quand on se trouve dans sa rubrique. --}}
<div x-data="{ ouvert: @js((bool) $ouvert) }" class="space-y-1">
    <button type="button" @click="ouvert = !ouvert"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-emerald-50/90 transition-colors hover:bg-white/10 hover:text-white"
            :aria-expanded="ouvert">
        @if ($icone)
            <span class="material-symbols-outlined text-xl">{{ $icone }}</span>
        @endif
        <span class="flex-1 truncate">{{ $titre }}</span>
        <span class="material-symbols-outlined text-lg transition-transform" :class="ouvert ? 'rotate-180' : ''">expand_more</span>
    </button>

    <div x-show="ouvert" x-cloak x-collapse class="space-y-0.5">
        {{ $slot }}
    </div>
</div>
