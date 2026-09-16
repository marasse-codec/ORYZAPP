<?php

namespace App\Http\Controllers;

use App\Enums\StatutDemandeE;
use App\Models\Demande;
use App\Utils\Helper;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $demandes = Demande::query()
            ->pourUtilisateur(Auth::user())
            ->with(['attribution', 'rapports', 'lignes.typeAppui.service'])
            ->get();

        $repartition = collect(StatutDemandeE::cases())
            ->mapWithKeys(fn (StatutDemandeE $statut) => [
                $statut->value => [
                    'statut' => $statut,
                    'total'  => $demandes->filter(fn (Demande $d) => 
                        $d->statut === $statut || $d->statut === $statut->value
                    )->count(),
                ],
            ]);

        return view('dashboard', [
            'total'       => $demandes->count(),
            'repartition' => $repartition,
            'layout'      => Helper::layout(),
        ]);
    }
}