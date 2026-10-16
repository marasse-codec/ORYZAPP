@php
    use App\Domain\Acces\Enums\PermissionEnum;
    use App\Domain\Acces\Enums\RoleEnum;
@endphp

{{-- Le menu se compose d'après les permissions du rôle connecté. Un groupe s'ouvre de lui-même quand on est dans sa rubrique. --}}

<x-ui.menu-lien :href="route('dashboard')" icone="space_dashboard" :actif="request()->routeIs('dashboard')">Tableau de bord</x-ui.menu-lien>

@can(PermissionEnum::voirPilotage->value)
    <x-ui.menu-lien :href="route('pilotage.index')" icone="speed" :actif="request()->routeIs('pilotage.*')">Pilotage</x-ui.menu-lien>
@endcan

@can(PermissionEnum::voirDemandes->value)
    <x-ui.menu-groupe titre="Demandes" icone="assignment" :ouvert="request()->routeIs('demandes.*')">
        @can(PermissionEnum::creerDemande->value)
            <x-ui.menu-lien :href="route('demandes.create')" :actif="request()->routeIs('demandes.create')">Nouvelle demande</x-ui.menu-lien>
            <x-ui.menu-lien :href="route('demandes.serie.create')" :actif="request()->routeIs('demandes.serie.*')">Saisie en série</x-ui.menu-lien>
        @endcan
        <x-ui.menu-lien :href="route('demandes.index')" :actif="request()->routeIs('demandes.index', 'demandes.show', 'demandes.edit')">Voir les demandes</x-ui.menu-lien>
    </x-ui.menu-groupe>
@endcan

@can(PermissionEnum::gererCommission->value)
    <x-ui.menu-groupe titre="Commission" icone="gavel" :ouvert="request()->routeIs('commissions.*', 'attributions.*')">
        <x-ui.menu-lien :href="route('commissions.index')" :actif="request()->routeIs('commissions.*')">Séances</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('attributions.index')" :actif="request()->routeIs('attributions.index', 'attributions.show', 'attributions.edit', 'attributions.create')">Attributions</x-ui.menu-lien>
        @can(PermissionEnum::gererSimulation->value)
            <x-ui.menu-lien :href="route('attributions.simulation')" :actif="request()->routeIs('attributions.simulation*')">Simuler les sorties</x-ui.menu-lien>
        @endcan
    </x-ui.menu-groupe>
@elsecan(PermissionEnum::voirAttributions->value)
    {{-- La comptabilité passe par son propre menu ci-dessous. --}}
    @unlessrole(RoleEnum::comptable->value)
        <x-ui.menu-lien :href="route('attributions.index')" icone="inventory_2" :actif="request()->routeIs('attributions.*')">Attributions</x-ui.menu-lien>
    @endunlessrole
@endcan

@can(PermissionEnum::gererReglements->value)
    <x-ui.menu-groupe titre="Comptabilité" icone="payments" :ouvert="request()->routeIs('comptabilite.*', 'reglements.*')">
        <x-ui.menu-lien :href="route('comptabilite.attributions')" :actif="request()->routeIs('comptabilite.attributions', 'comptabilite.reglement')">Attributions à encaisser</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('comptabilite.reglements', ['etat' => 'brouillon'])" :actif="request()->routeIs('comptabilite.reglements') && request('etat') !== 'soumis'">Règlements en brouillon</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('comptabilite.reglements', ['etat' => 'soumis'])" :actif="request()->routeIs('comptabilite.reglements') && request('etat') === 'soumis'">Règlements soumis</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('reglements.index')" :actif="request()->routeIs('reglements.*')">Synthèse de caisse</x-ui.menu-lien>
    </x-ui.menu-groupe>
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

@canany([PermissionEnum::voirAttributions->value, PermissionEnum::voirDemandes->value, PermissionEnum::voirStocks->value, PermissionEnum::attribuerAppui->value])
    <x-ui.menu-groupe titre="Éditions" icone="print" :ouvert="request()->routeIs('editions.*')">
        @can(PermissionEnum::voirAttributions->value)
            <x-ui.menu-lien :href="route('editions.attributions.index')" :actif="request()->routeIs('editions.attributions.*')">Attributions (export)</x-ui.menu-lien>
        @endcan
        @can(PermissionEnum::voirDemandes->value)
            <x-ui.menu-lien :href="route('editions.demandes.index')" :actif="request()->routeIs('editions.demandes.*')">Demandes (export)</x-ui.menu-lien>
        @endcan
        @can(PermissionEnum::voirStocks->value)
            <x-ui.menu-lien :href="route('editions.stocks.index')" :actif="request()->routeIs('editions.stocks.*')">Stocks (export)</x-ui.menu-lien>
        @endcan
        @can(PermissionEnum::attribuerAppui->value)
            <x-ui.menu-lien :href="route('editions.arbitrages.index')" :actif="request()->routeIs('editions.arbitrages.*')">Fiches d'arbitrage</x-ui.menu-lien>
        @endcan
    </x-ui.menu-groupe>
@endcanany

@can(PermissionEnum::voirStatistiques->value)
    <x-ui.menu-groupe titre="Statistiques" icone="monitoring" :ouvert="request()->routeIs('statistiques.*')">
        <x-ui.menu-lien :href="route('statistiques.index')" :actif="request()->routeIs('statistiques.index')">Par année et cycle</x-ui.menu-lien>
        <x-ui.menu-lien :href="route('statistiques.comparaison')" :actif="request()->routeIs('statistiques.comparaison')">Comparer les cycles</x-ui.menu-lien>
    </x-ui.menu-groupe>
@endcan

@hasrole(RoleEnum::admin->value)
    <x-ui.menu-lien href="/administration" icone="admin_panel_settings">Administration</x-ui.menu-lien>
@endhasrole
