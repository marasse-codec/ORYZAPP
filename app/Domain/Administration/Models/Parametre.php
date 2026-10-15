<?php

namespace App\Domain\Administration\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Un paramètre général enregistré par l'administrateur (clé => valeur).
 * Les clés possibles et leurs valeurs par défaut sont dans ParametreEnum :
 * on passe par ParametreService pour lire ou modifier, jamais directement.
 */
#[Fillable(['cle', 'valeur'])]
#[Table('parametres')]
class Parametre extends Model
{
    protected $primaryKey = 'id_parametre';
}
