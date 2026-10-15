<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Services\RendementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\EnvoyerRendementsRequest;

/** Les rendements des superficies d'une demande attribuée : réservés au DP qui l'a émise (cf. la requête). */
class RendementController extends Controller
{
    public function update(EnvoyerRendementsRequest $request, Attribution $attribution, RendementService $rendements)
    {
        $rendements->enregistrer($attribution, $request->validated()['rendements']);

        return redirect()
            ->route('attributions.show', $attribution->id_attribution)
            ->with('success', 'Rendements transmis.');
    }
}
