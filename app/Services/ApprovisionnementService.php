<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Approvisionnement;
use App\Models\Appui;
use App\Models\MouvementStock;
use App\Models\TypeMouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovisionnementService
{
    public function store(Request $request)
    {
        $valide = $request->validate([
            'id_appui' => 'required|exists:appuis,id_appui',
            'quantite_ajoutee' => 'required|integer|min:1',
            'date_approvisionnement' => 'required|date|before_or_equal:today',
        ], [
            'date_approvisionnement.before_or_equal' => 'Un approvisionnement ne peut être daté du futur.',
        ]);

        DB::transaction(function () use ($valide) {

            // Verrouillage de l'appui pendant toute l'opération.
            $appui = Appui::lockForUpdate()
                ->findOrFail($valide['id_appui']);

            $quantiteAvant = $appui->quantite_en_stock;
            $quantiteAjoutee = $valide['quantite_ajoutee'];

            // 1. Enregistrement de l'approvisionnement
            $approvisionnement = Approvisionnement::create([

                'date_approvisionnement' => $valide['date_approvisionnement'],
            ]);

            // 2. Mise à jour du stock
            $appui->increment(
                'quantite_en_stock',
                $quantiteAjoutee
            );

            $idTypeMouvement = TypeMouvementStock::where('code', 'ENTREE')->value('id_type_mouvement_stock');

            // 3. Création du mouvement de stock
            MouvementStock::create([
                'id_appui' => $appui->id_appui,
                'id_type_mouvement_stock' => $idTypeMouvement,
                'id_approvisionnement' => $approvisionnement->id_approvisionnement,
                'id_agent' => Agent::where('user_id', auth()->id())->value('id_agent'),
                'quantite' => $quantiteAjoutee,
                'date_mouvement' => now(),
                'motif' => 'Approvisionnement #'.$approvisionnement->id_approvisionnement,
            ]);
        });

        return redirect()
            ->route('approvisionnements.index')
            ->with(
                'success',
                'Approvisionnement enregistré, le stock a été mis à jour.'
            );
    }
}
