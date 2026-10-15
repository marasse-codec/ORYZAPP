<?php

namespace App\Domain\Acces\Enums;

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

    // --- Ajouts v2
    // Le directeur pilote : il consulte tout (chiffres, comparaisons, rapports) sans rien modifier.
    case directeur = 'directeur';
    // Le comptable tient la caisse : règlements des attributions et suivi des impayés.
    case comptable = 'comptable';

    public function libelle(): string
    {
        return match ($this) {
            self::admin => 'Administrateur',
            self::acteur => 'Acteur de la filière',
            self::dp => 'Délégué de pôle',
            self::agentService => 'Agent de service',
            self::agentCommission => 'Agent de commission',
            self::gestionnaireStock => 'Gestionnaire de stock',
            self::directeur => 'Directeur',
            self::comptable => 'Comptable',
        };
    }

    /**
     * Saisir ou modifier une demande est ouvert à tout agent connecté, sauf à la
     * direction (lecture seule) et à la comptabilité (caisse uniquement).
     */
    public function peutSaisirDesDemandes(): bool
    {
        return ! in_array($this, [self::directeur, self::comptable], true);
    }

    /**
     * Les droits que le rôle reçoit à sa création. Ensuite, c'est l'administrateur qui
     * les ajuste depuis l'écran « Rôles et droits » ; l'administrateur, lui, a tout.
     *
     * @return array<int, PermissionEnum>
     */
    public function droitsParDefaut(): array
    {
        return match ($this) {
            self::admin => PermissionEnum::cases(),
            self::acteur => [PermissionEnum::creerDemande],
            self::dp => [PermissionEnum::creerDemande, PermissionEnum::editDemande],
            // Lecture seule : ses demandes et attributions se filtrent par service ; il télécharge les stocks de ses appuis.
            self::agentService => [PermissionEnum::voirStocks],
            self::agentCommission => [PermissionEnum::gererCommission, PermissionEnum::gererSimulation, PermissionEnum::attribuerAppui],
            self::gestionnaireStock => [PermissionEnum::gererStock, PermissionEnum::gererMouvement, PermissionEnum::voirStocks],
            self::directeur => [PermissionEnum::voirPilotage, PermissionEnum::voirStatistiques, PermissionEnum::voirStocks, PermissionEnum::telechargerRapports],
            self::comptable => [PermissionEnum::gererReglements, PermissionEnum::telechargerRapports],
        };
    }
}
