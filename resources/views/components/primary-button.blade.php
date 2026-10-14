<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-[--color-primary] px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-[--color-primary-dark] focus:outline-none focus-visible:ring-2 focus-visible:ring-[--color-primary] focus-visible:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
