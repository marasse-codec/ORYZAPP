@extends($layout ?? 'layouts.aderiz')

@section('titre-page', 'Mon profil')

@section('content')
<x-ui.page titre="Mon profil" sous-titre="Vos informations de connexion et la sécurité de votre compte." class="max-w-3xl">
    <x-ui.card>
        @include('profile.partials.update-profile-information-form')
    </x-ui.card>

    <x-ui.card>
        @include('profile.partials.update-password-form')
    </x-ui.card>

    <x-ui.card>
        @include('profile.partials.delete-user-form')
    </x-ui.card>
</x-ui.page>
@endsection
