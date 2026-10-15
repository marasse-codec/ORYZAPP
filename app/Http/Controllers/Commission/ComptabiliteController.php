<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\Paiement;
use App\Domain\Commission\Services\ReglementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\EnregistrerPaiementRequest;
use App\Utils\Helper;
use Illuminate\Http\Request;

/**
 * L'espace du comptable (permission « gerer-reglements », cf. routes) :
 *  - retrouver une attribution à encaisser (numéro d'attribution, de demande, téléphone ou nom) ;
 *  - ouvrir sa page de règlement, saisir un règlement en brouillon, le corriger, le soumettre ;
 *  - suivre les brouillons et les règlements soumis.
 */
class ComptabiliteController extends Controller
{
    /** Les attributions publiées à encaisser, avec la recherche. */
    public function attributions(Request $request, ReglementService $reglements)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'toutes' => 'nullable|boolean']);

        return view('comptabilite.attributions', [
            'layout' => Helper::layout(),
            'terme' => $request->input('q'),
            'toutes' => $request->boolean('toutes'),
            'attributions' => $reglements->rechercherAttributions($request->input('q'), ! $request->boolean('toutes')),
        ]);
    }

    /** La page de règlement d'une attribution : son détail, ses règlements, et le formulaire. */
    public function reglement(Attribution $attribution)
    {
        abort_unless($attribution->estPubliee(), 404, "Cette attribution n'est pas encore publiée : rien n'est dû.");

        $attribution->load(['demande.acteur', 'demande.localites', 'lignesAttributions.appui.uniteDosage', 'paiements.encaissePar', 'paiements.soumisPar']);

        return view('comptabilite.reglement', [
            'layout' => Helper::layout(),
            'attribution' => $attribution,
            'modes' => ModePaiementEnum::cases(),
        ]);
    }

    public function creer(EnregistrerPaiementRequest $request, Attribution $attribution, ReglementService $reglements)
    {
        $reglements->creerBrouillon($attribution, $request->validated());

        return redirect()->route('comptabilite.reglement', $attribution)
            ->with('success', 'Règlement enregistré en brouillon. Vérifiez-le, puis soumettez-le.');
    }

    public function modifier(EnregistrerPaiementRequest $request, Paiement $paiement, ReglementService $reglements)
    {
        $reglements->modifierBrouillon($paiement, $request->validated());

        return redirect()->route('comptabilite.reglement', $paiement->id_attribution)->with('success', 'Brouillon modifié.');
    }

    public function supprimer(Paiement $paiement, ReglementService $reglements)
    {
        $reglements->supprimerBrouillon($paiement);

        return redirect()->route('comptabilite.reglement', $paiement->id_attribution)->with('success', 'Brouillon supprimé.');
    }

    public function soumettre(Paiement $paiement, ReglementService $reglements)
    {
        $reglements->soumettre($paiement);

        return redirect()->route('comptabilite.reglement', $paiement->id_attribution)->with('success', 'Règlement soumis : il est désormais pris en compte.');
    }

    /** Les règlements : en brouillon (à soumettre) ou soumis (historique). */
    public function reglements(Request $request, ReglementService $reglements)
    {
        $etat = $request->input('etat') === 'soumis' ? 'soumis' : 'brouillon';

        return view('comptabilite.reglements', [
            'layout' => Helper::layout(),
            'etat' => $etat,
            'paiements' => $etat === 'soumis' ? $reglements->derniersPaiements(100) : $reglements->brouillons(),
        ]);
    }
}
