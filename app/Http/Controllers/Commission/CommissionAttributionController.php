<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Services\SeanceService;
use App\Domain\Commission\Services\SyntheseCommissionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\OuvrirSeanceRequest;
use App\Utils\Helper;

/**
 * Les séances de commission. Une séance ouverte accueille les attributions ;
 * sa clôture rend les bons servables au magasin.
 */
class CommissionAttributionController extends Controller
{
    public function index()
    {
        /**@var Collection $sessions */
        $sessions = SessionCommission::withCount('attributions')
                ->orderByDesc('date_debut_session')
                ->get();

        return view('commissions.index', [
            'sessions' => $sessions,
            'layout' => Helper::layout()
        ]);
    }

    public function create()
    {
        return view('commissions.create', ['layout' => Helper::layout()]);
    }

    public function store(OuvrirSeanceRequest $request, SeanceService $seances)
    {
        $session = $seances->ouvrir($request->validated('resume'));

        return redirect()->route('commissions.show', $session->id_session)
            ->with('success', 'Séance de commission ouverte.');
    }

    /**
     * La séance : ce qu'elle a déjà attribué, et, tant qu'elle est ouverte,
     * les dossiers instruits qui attendent son arbitrage.
     */
    public function show(string $id, SyntheseCommissionService $synthese)
    {
        $session = SessionCommission::with([
            'attributions.demande.acteur',
            'attributions.lignesAttributions.appui',
        ])->findOrFail($id);

        $enAttente = $session->estOuverte()
            ? $synthese->dossiersEnAttente()
            : collect();
        
        $publiable = $session->estPubliable();

        return view('commissions.show', [
            'session' => $session,
            'finie' => $session->estFinie(),
            'publiable' => $publiable,
            'enAttente' => $enAttente,
            'layout' => Helper::layout(),
        ]);
    }

    /**
     * La synthèse imprimable, pour les séances qui se tiennent en présentiel :
     * la confrontation globale, le détail de chaque dossier avec des colonnes
     * à remplir à la main, et une feuille d'émargement.
     */
    public function synthese(SyntheseCommissionService $synthese)
    {
        $dossiers = $synthese->dossiersEnAttente();

        return view('commissions.synthese', [
            'dossiers' => $dossiers,
            'confrontation' => $synthese->confrontation($dossiers),
        ]);
    }

    public function cloturer(string $id, SeanceService $seances)
    {
        $seances->cloturer(SessionCommission::findOrFail($id));

        return redirect()->route('commissions.index')
            ->with('success', 'Séance clôturée.');
    }
}
