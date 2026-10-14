<?php

namespace App\Domain\Acces\Enums;

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

    public function libelle(): string
    {
        return match ($this) {
            self::creerDemande => 'Créer une demande',
            self::gererCommission => 'Gérer les sessions de commission',
            self::attribuerAppui => 'Attribuer des appuis',
            self::gererStock => 'Gérer les stocks',
            self::editDemande => 'Modifier une demande',
            self::gererMouvement => 'gerer-mouvement',
            self::creerMouvement => 'creer-mouvement',
            self::gererEquipement => 'Gérer les équipements',
            self::gererRessources => 'Gérer les ressources',
            self::gererSimulation => 'Gérer les simulations d\'attribution',
            self::voirStatistiques => 'Consulter les statistiques',
            self::voirJournal => 'Consulter le journal des actions',
            self::voirStocks => 'Consulter et télécharger les stocks de son service'
        };
    }
}
