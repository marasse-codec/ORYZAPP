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

    // --- Ajouts v2 : administration, accès et historisation
    case compteDesactive = 'COMPTE_DESACTIVE';
    case compteReactive = 'COMPTE_REACTIVE';
    case droitsModifies = 'DROITS_MODIFIES';
    case parametresModifies = 'PARAMETRES_MODIFIES';
    case referentielModifie = 'REFERENTIEL_MODIFIE';
    case referentielSupprime = 'REFERENTIEL_SUPPRIME';
    case campagneCloturee = 'CAMPAGNE_CLOTUREE';
    case campagneRouverte = 'CAMPAGNE_ROUVERTE';
    case connexion = 'CONNEXION';
    case connexionEchouee = 'CONNEXION_ECHOUEE';
    case deconnexion = 'DECONNEXION';
    case exportTelecharge = 'EXPORT_TELECHARGE';

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
            self::compteDesactive => 'Compte désactivé',
            self::compteReactive => 'Compte réactivé',
            self::droitsModifies => "Droits d'un rôle modifiés",
            self::parametresModifies => 'Paramètres généraux modifiés',
            self::referentielModifie => 'Référentiel modifié',
            self::referentielSupprime => 'Élément de référentiel supprimé',
            self::campagneCloturee => 'Campagne clôturée',
            self::campagneRouverte => 'Campagne rouverte',
            self::connexion => 'Connexion',
            self::connexionEchouee => 'Connexion refusée',
            self::deconnexion => 'Déconnexion',
            self::exportTelecharge => 'Export ou rapport téléchargé',
        };
    }
}
