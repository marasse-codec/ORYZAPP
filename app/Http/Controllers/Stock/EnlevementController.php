<?php

namespace App\Http\Controllers\Stock;

use App\Domain\Commission\Models\Attribution;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;

class EnlevementController extends Controller
{
    /**
     * Les bons servables : ceux dont les résultats sont publiés. Le magasinier
     * retrouve un bon soit par son numéro, soit par le téléphone du demandeur
     * c'est tout ce que le récipiendaire a sur lui.
     */
    public function index(Request $request)
    {
        $recherche = trim((string) $request->query('recherche', ''));

        $enlevements = Attribution::query()
            ->with(['lignesAttributions.appui', 'demande.acteur', 'session'])
            ->whereNotNull('date_publication')
            ->when($recherche !== '', function ($query) use ($recherche) {
                // Un numéro saisi seul vaut pour l'identifiant du bon comme
                // pour le téléphone : on cherche des deux côtés.
                $numero = ltrim(preg_replace('/\D/', '', $recherche), '0');

                $query->where(function ($q) use ($recherche, $numero) {
                    if ($numero !== '') {
                        $q->where('id_attribution', $numero);
                    }

                    $q->orWhereHas('demande.acteur', fn ($a) => $a
                        ->whereLike('telephone', "%{$recherche}%")
                        ->orWhereLike('nom', "%{$recherche}%"));
                });
            })
            ->orderByDesc('id_attribution')
            ->get();

        return view('enlevements.index', [
            'enlevements' => $enlevements,
            'recherche' => $recherche,
            'layout' => Helper::layout(),
        ]);
    }

    public function create(string $idAttribution)
    {
        $attribution = Attribution::with([
            'demande.acteur',
            'session',
            'lignesAttributions.appui.equipements',
            'lignesAttributions.appui.uniteDosage',
            'lignesAttributions.appui.typeAppui',

        ])->findOrFail($idAttribution);

        if (! $attribution->estDestockable()) {
            return redirect()->route('enlevements.index')
                ->with('error', 'Ce bon a déjà été servi, ou ses résultats ne sont pas encore publiés.');
        }

        return view('enlevements.create', [
            'attribution' => $attribution,
            'layout' => Helper::layout(),
        ]);
    }
}
