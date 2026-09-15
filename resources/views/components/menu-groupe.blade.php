@props(['titre'])

<div x-data="{ open: false }" class="relative border-b border-slate-700">
    <button @click="open = !open"
            class="flex items-center justify-between w-full p-3 text-white hover:bg-slate-700">
        <span>{{ $titre }}</span>
        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="pl-4 py-1 space-y-1 bg-slate-900">
        {{ $slot }}
    </div>
</div>
