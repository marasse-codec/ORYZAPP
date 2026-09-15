<?php

namespace App\Http\Controllers;

use App\Models\SessionCommission;
use App\Services\SyntheseCommissionService;
use App\Utils\Helper;
use Illuminate\Http\Request;

/**
 * Les séances de commission. Une séance ouverte accueille les attributions ;
 * sa clôture rend les bons servables au magasin.
 */
class CommissionAttributionController extends Controller
{
    public function index()
    {
        return view('commissions.index', [
            'sessions' => SessionCommission::withCount('attributions')
                ->orderByDesc('date_debut_session')
                ->get(),
            'layout' => Helper::layout(),
        ]);
    }

    public function create()
    {
        return view('commissions.create', ['layout' => Helper::layout()]);
    }

    public function store(Request $request)
    {
        $valide = $request->validate([
            'resume' => 'nullable|string|max:1000',
        ]);

        $session = SessionCommission::create([
            'resume' => $valide['resume'] ?? null,
            'date_debut_session' => today()->toDateString(),
        ]);

        return redirect()->route('commissions.show', $session->id_session)
            ->with('success', 'Séance de commission ouverte.');
    }

    /**
     * La séance : ce qu'elle a déjà attribué, et — tant qu'elle est ouverte —
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

        return view('commissions.show', [
            'session' => $session,
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

    public function cloturer(string $id)
    {
        $session = SessionCommission::findOrFail($id);

        if (! $session->estOuverte()) {
            return back()->with('error', 'Cette séance est déjà clôturée.');
        }

        $session->update(['date_fin_session' => today()->toDateString()]);

        return redirect()->route('commissions.index')
            ->with('success', 'Séance clôturée.');
    }
}
