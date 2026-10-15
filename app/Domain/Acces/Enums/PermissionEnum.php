<?php

namespace App\Domain\Acces\Enums;

/**
 * Les droits élémentaires. Un rôle regroupe des droits ; l'administrateur peut
 * changer les droits de chaque rôle depuis l'écran « Rôles et droits ».
 */
enum PermissionEnum: string
{
    case creerDemande = 'creer-demande';
    case editDemande = 'edit-demande';
    case gererCommission = 'gerer-commission';
    case attribuerAppui = 'attribuer-appui';
    case gererStock = 'gerer-stock';
    case gererMouvement = 'gerer-mouvement';
    case creerMouvement = 'creer-mouvement';
    case gererEquipement = 'gerer-equipement';
    case gererRessources = 'gerer-ressources';
    case gererSimulation = 'gerer-simulation';
    case voirStatistiques = 'voir-statistiques';
    case voirJournal = 'voir-journal';
    case voirStocks = 'voir-stocks';
    case gererReglements = 'gerer-reglements';

    // --- Ajouts v2
    case voirPilotage = 'voir-pilotage';
    case telechargerRapports = 'telecharger-rapports';
    case voirDemandes = 'voir-demandes';
    case voirAttributions = 'voir-attributions';

    public function libelle(): string
    {
        return match ($this) {
            self::creerDemande => 'Créer une demande',
            self::gererCommission => 'Gérer les sessions de commission',
            self::attribuerAppui => 'Attribuer des appuis',
            self::gererStock => 'Gérer les stocks',
            self::editDemande => 'Modifier une demande',
            self::gererMouvement => 'Gérer les mouvements de stock',
            self::creerMouvement => 'Créer un mouvement de stock',
            self::gererEquipement => 'Gérer les équipements',
            self::gererRessources => 'Gérer les ressources',
            self::gererSimulation => 'Gérer les simulations d\'attribution',
            self::voirStatistiques => 'Consulter les statistiques',
            self::voirJournal => 'Consulter le journal des actions',
            self::voirStocks => 'Consulter et télécharger les stocks de son service',
            self::gererReglements => 'Enregistrer les règlements des attributions et suivre les impayés',
            self::voirPilotage => 'Consulter le tableau de pilotage (direction)',
            self::telechargerRapports => 'Télécharger les rapports de campagne (PDF)',
            self::voirDemandes => 'Consulter les demandes (selon son périmètre)',
            self::voirAttributions => 'Consulter les attributions (selon son périmètre)',
        };
    }
}
