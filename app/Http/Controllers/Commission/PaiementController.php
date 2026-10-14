<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Services\ReglementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\EnregistrerPaiementRequest;

/** Les règlements d'une attribution : réservés à qui a la permission « gerer-reglements » (cf. routes). */
class PaiementController extends Controller
{
    public function store(EnregistrerPaiementRequest $request, Attribution $attribution, ReglementService $reglements)
    {
        $reglements->enregistrerPaiement($attribution, $request->validated());

        return redirect()
            ->route('attributions.show', $attribution->id_attribution)
            ->with('success', 'Règlement enregistré.');
    }
}
