<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[--color-primary] focus-visible:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
