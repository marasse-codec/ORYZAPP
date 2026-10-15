<?php

namespace App\Domain\Journal\Enums;

/**
 * Les actions que le journal garde en mémoire (SNF08). Le code est stocké tel
 * quel dans journals_actions.action ; le libellé sert à l'affichage.
 */
enum ActionJournalEnum: string
{
    case demandeCreee = 'DEMANDE_CREEE';
    case demandeModifiee = 'DEMANDE_MODIFIEE';
    case demandeSupprimee = 'DEMANDE_SUPPRIMEE';
    case demandeSoumise = 'DEMANDE_SOUMISE';
    case demandeVisitee = 'DEMANDE_VISITEE';
    case demandeRejetee = 'DEMANDE_REJETEE';

    case sessionOuverte = 'SESSION_OUVERTE';
    case sessionCloturee = 'SESSION_CLOTUREE';

    case attributionCreee = 'ATTRIBUTION_CREEE';
    case attributionModifiee = 'ATTRIBUTION_MODIFIEE';
    case attributionSupprimee = 'ATTRIBUTION_SUPPRIMEE';
    case attributionPubliee = 'ATTRIBUTION_PUBLIEE';
    case attributionRetiree = 'ATTRIBUTION_RETIREE';
    case paiementEnregistre = 'PAIEMENT_ENREGISTRE';
    case rendementEnvoye = 'RENDEMENT_ENVOYE';

    case approvisionnementEnregistre = 'APPROVISIONNEMENT_ENREGISTRE';
    case equipementRestitue = 'EQUIPEMENT_RESTITUE';

    case appuiCree = 'APPUI_CREE';
    case appuiModifie = 'APPUI_MODIFIE';
    case appuiSupprime = 'APPUI_SUPPRIME';

    case compteCree = 'COMPTE_CREE';
    case compteModifie = 'COMPTE_MODIFIE';

    public function libelle(): string
    {
        return match ($this) {
            self::demandeCreee => 'Demande créée',
            self::demandeModifiee => 'Demande modifiée',
            self::demandeSupprimee => 'Demande supprimée',
            self::demandeSoumise => 'Demande soumise',
            self::demandeVisitee => 'Visite de terrain enregistrée',
            self::demandeRejetee => 'Demande rejetée',
            self::sessionOuverte => 'Séance ouverte',
            self::sessionCloturee => 'Séance clôturée',
            self::attributionCreee => 'Attribution créée',
            self::attributionModifiee => 'Attribution modifiée',
            self::attributionSupprimee => 'Attribution supprimée',
            self::attributionPubliee => 'Attribution publiée',
            self::attributionRetiree => 'Appuis retirés au magasin',
            self::paiementEnregistre => 'Règlement enregistré',
            self::rendementEnvoye => 'Rendements des superficies transmis',
            self::approvisionnementEnregistre => 'Approvisionnement enregistré',
            self::equipementRestitue => 'Équipement restitué',
            self::appuiCree => 'Appui ajouté au catalogue',
            self::appuiModifie => 'Appui modifié',
            self::appuiSupprime => 'Appui retiré du catalogue',
            self::compteCree => 'Compte utilisateur créé',
            self::compteModifie => 'Compte utilisateur modifié',
        };
    }
}
