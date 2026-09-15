<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case creerDemande = 'creer-demande';
    case editDemande = 'edit-demande';
    case instruireDemande = 'instruire-demande';
    case gererCommission = 'gerer-commission';
    case attribuerAppui = 'attribuer-appui';
    case gererStock = 'gerer-stock';

    public function libelle(): string
    {
        return match ($this) {
            self::creerDemande => 'Créer une demande',
            self::instruireDemande => 'Instruire une demande',
            self::gererCommission => 'Gérer les sessions de commission',
            self::attribuerAppui => 'Attribuer des appuis',
            self::gererStock => 'Gérer les stocks',
            self::editDemande => 'Modifier une demande'
        };
    }
}
