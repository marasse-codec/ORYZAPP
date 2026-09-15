<?php

namespace App\Enums;

enum RoleEnum: string
{
    case admin = 'admin';
    case acteur = 'acteur';
    case dp = 'dp';
    case agentService = 'agent-service';
    // L'agent de commission ouvre et clôture les séances autant qu'il arbitre :
    // les deux rôles n'en font qu'un.
    case agentCommission = 'agent-commission';
    case gestionnaireStock = 'gestionnaire-stock';

    public function libelle(): string
    {
        return match ($this) {
            self::admin => 'Administrateur',
            self::acteur => 'Acteur de la filière',
            self::dp => 'Délégué de pôle',
            self::agentService => 'Agent de service',
            self::agentCommission => 'Agent de commission',
            self::gestionnaireStock => 'Gestionnaire de stock',
        };
    }
}
