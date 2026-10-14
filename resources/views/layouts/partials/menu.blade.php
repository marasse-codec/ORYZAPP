@php
    use App\Domain\Acces\Enums\PermissionEnum;
    use App\Domain\Acces\Enums\RoleEnum;
@endphp

{{-- Le menu se compose d'après les permissions du rôle connecté. Un groupe s'ouvre de lui-même quand on est dans sa rubrique. --}}

<x-ui.menu-lien :href="route('dashboard')" icone="space_dashboard" :actif="request()->routeIs('dashboard')">Tableau de bord</x-ui.menu-lien>

<x-ui.menu-groupe titre="Demandes" icone="assignment" :ouvert="request()->routeIs('demandes.*')">
    @can(PermissionEnum::creerDemande->value)
        <x-ui.menu-lien :href="route('demandes.create')" :actif="request()->routeIs('demandes.create')">Nouvelle demande</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('demandes.serie.create')" :actif="request()->routeIs('demandes.serie.*')">Saisie en série</x-ui.menu-lien>
    @endcan
    <x-ui.menu-lien :href="route('demandes.index')" :actif="request()->routeIs('demandes.index', 'demandes.show', 'demandes.edit')">Voir les demandes</x-ui.menu-lien>
</x-ui.menu-groupe>

@can(PermissionEnum::gererCommission->value)
    <x-ui.menu-groupe titre="Commission" icone="gavel" :ouvert="request()->routeIs('commissions.*', 'attributions.*')">
        <x-ui.menu-lien :href="route('commissions.index')" :actif="request()->routeIs('commissions.*')">Séances</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('attributions.index')" :actif="request()->routeIs('attributions.index', 'attributions.show', 'attributions.edit', 'attributions.create')">Attributions</x-ui.menu-lien>
        @can(PermissionEnum::gererSimulation->value)
            <x-ui.menu-lien :href="route('attributions.simulation')" :actif="request()->routeIs('attributions.simulation*')">Simuler les sorties</x-ui.menu-lien>
        @endcan
    </x-ui.menu-groupe>
@else
    <x-ui.menu-lien :href="route('attributions.index')" icone="inventory_2" :actif="request()->routeIs('attributions.*')">Attributions</x-ui.menu-lien>
@endcan

@can(PermissionEnum::gererStock->value)
    <x-ui.menu-groupe titre="Magasin" icone="warehouse" :ouvert="request()->routeIs('approvisionnements.*', 'enlevements.*', 'equipements.*', 'mouvements-stocks.*', 'appuis.*')">
        <x-ui.menu-lien :href="route('approvisionnements.index')" :actif="request()->routeIs('approvisionnements.*')">Approvisionnements</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('enlevements.index')" :actif="request()->routeIs('enlevements.*')">Enlèvements</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('equipements.retour.create')" :actif="request()->routeIs('equipements.retour.*')">Restitutions</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('mouvements-stocks.index')" :actif="request()->routeIs('mouvements-stocks.*')">Mouvements de stock</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('appuis.index')" :actif="request()->routeIs('appuis.*')">Catalogue des appuis</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('equipements.index')" :actif="request()->routeIs('equipements.index')">Équipements</x-ui.menu-lien>
    </x-ui.menu-groupe>
@endcan

<x-ui.menu-groupe titre="Éditions" icone="print" :ouvert="request()->routeIs('editions.*')">
    <x-ui.menu-lien :href="route('editions.attributions.index')" :actif="request()->routeIs('editions.attributions.*')">Attributions (export)</x-ui.menu-lien>
    <x-ui.menu-lien :href="route('editions.demandes.index')" :actif="request()->routeIs('editions.demandes.*')">Demandes (export)</x-ui.menu-lien>
    @can(PermissionEnum::voirStocks->value)
        <x-ui.menu-lien :href="route('editions.stocks.index')" :actif="request()->routeIs('editions.stocks.*')">Stocks (export)</x-ui.menu-lien>
    @endcan
    @can(PermissionEnum::attribuerAppui->value)
        <x-ui.menu-lien :href="route('editions.arbitrages.index')" :actif="request()->routeIs('editions.arbitrages.*')">Fiches d'arbitrage</x-ui.menu-lien>
    @endcan
</x-ui.menu-groupe>

@can(PermissionEnum::voirStatistiques->value)
    <x-ui.menu-lien :href="route('statistiques.index')" icone="monitoring" :actif="request()->routeIs('statistiques.*')">Statistiques</x-ui.menu-lien>
@endcan

@hasrole(RoleEnum::admin->value)
    <x-ui.menu-lien href="/administration" icone="admin_panel_settings">Administration</x-ui.menu-lien>
@endhasrole
