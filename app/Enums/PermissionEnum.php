<?php

namespace App\Enums;

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
            self::gererSimulation => 'Gérer les simulations d\'attribution'
        };
    }
}
