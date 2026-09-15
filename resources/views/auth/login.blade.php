<x-guest-layout>

    <header class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Connexion à votre espace</h2>
        <p class="mt-2 text-sm text-slate-500">
            Renseignez les identifiants qui vous ont été communiqués par l'administrateur.
        </p>
    </header>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Adresse e-mail')" />
            <div class="relative mt-1.5">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    mail
                </span>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       placeholder="ex : nom.prenom@aderiz.ci"
                       required autofocus autocomplete="username"
                       class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-[--color-primary] focus:ring-[--color-primary]">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div x-data="{ visible: false }">
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Mot de passe')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-medium text-[--color-primary] hover:underline">
                        {{ __('Mot de passe oublié ?') }}
                    </a>
                @endif
            </div>

            <div class="relative mt-1.5">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    lock
                </span>
                <input id="password" name="password" :type="visible ? 'text' : 'password'"
                       required autocomplete="current-password"
                       class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-10 text-sm shadow-sm transition focus:border-[--color-primary] focus:ring-[--color-primary]">
                {{-- Bascule d'affichage : une saisie masquée est la première
                     cause d'échec de connexion sur mobile. --}}
                <button type="button" @click="visible = !visible" tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                        :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                    <span class="material-symbols-outlined text-lg" x-text="visible ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="rounded border-slate-300 text-[--color-primary] shadow-sm focus:ring-[--color-primary]">
            <span class="text-sm text-slate-600">{{ __('Rester connecté sur cet appareil') }}</span>
        </label>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[--color-primary] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2">
            {{ __('Se connecter') }}
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </button>
    </form>

    <footer class="mt-8 space-y-3 border-t border-slate-200 pt-6 text-center text-sm">
        @if (Route::has('register'))
            <p class="text-slate-500">
                Pas encore de compte ?
                <a href="{{ route('register') }}" class="font-semibold text-[--color-primary] hover:underline">
                    Créer un compte
                </a>
            </p>
        @endif
        <p>
            <a href="{{ route('vitrine') }}" class="text-slate-500 hover:text-[--color-primary] hover:underline">
                Comment faire une demande d'appui ?
            </a>
        </p>
    </footer>
</x-guest-layout>
