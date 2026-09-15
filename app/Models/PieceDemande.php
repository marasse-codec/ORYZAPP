<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PieceDemande extends Model
{
    protected $table = 'pieces_demandes';

    protected $primaryKey = 'id_piece';

    protected $fillable = ['id_demande', 'id_type_piece', 'chemin_fichier'];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function typePiece(): BelongsTo
    {
        return $this->belongsTo(TypePiece::class, 'id_type_piece', 'id_type_piece');
    }

    // ------------------------------------------------------------------ Fichier

    /**
     * Les pièces sont déposées sur le disque public, servi par le lien
     * symbolique `public/storage`. L'adresse se construit sur la racine de la
     * requête en cours plutôt que sur APP_URL : une image dont l'hôte est
     * faux ne s'imprimerait pas.
     */
    public function url(): string
    {
        return asset('storage/'.$this->chemin_fichier);
    }

    public function nomFichier(): string
    {
        return basename($this->chemin_fichier);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->chemin_fichier, PATHINFO_EXTENSION));
    }

    /**
     * Une image se pose dans la page et part à l'imprimante avec elle. Un PDF,
     * lui, n'est pas restitué par le navigateur dans le flux d'impression de
     * la page qui l'incorpore : il se tire depuis sa propre fenêtre.
     */
    public function estImage(): bool
    {
        return in_array($this->extension(), ['jpg', 'jpeg', 'png'], true);
    }

    public function estPdf(): bool
    {
        return $this->extension() === 'pdf';
    }
}
