@extends($layout ?? 'layouts.aderiz')

@section('content')

    @include('partials.flash')

    <!-- Composant PowerGrid -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <livewire:equipements-table />
    </div>

@endsection