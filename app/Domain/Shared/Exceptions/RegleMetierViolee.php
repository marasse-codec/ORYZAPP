<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

/**
 * Une règle du métier refuse l'opération demandée (séance clôturée, stock
 * insuffisant, visite manquante…). Le domaine la lève avec un message destiné
 * à l'utilisateur ; la couche HTTP la transforme en réponse (cf. bootstrap/app.php).
 *
 * Le domaine ne dépend ainsi ni de la validation de formulaire ni des
 * redirections : il dit seulement pourquoi il refuse.
 */
class RegleMetierViolee extends DomainException
{
}
