<?php

namespace App\Http\Controllers\Stock;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Referentiel\Models\Service;
use App\Domain\Suivi\Exports\StocksExport;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/** L'état des stocks d'un service : aperçu et téléchargement Excel. */
class EditionStockController extends Controller
{
    public function index(Request $request)
    {
        $idService = $request->integer('id_service') ?: null;
        $estAgentService = $request->user()->hasRole(RoleEnum::agentService->value);

        return view('editions.stocks.index', [
            'appuis' => StocksExport::perimetre($idService)->with(['typeAppui.service', 'uniteDosage'])->orderBy('libelle')->get(),
            // L'agent de service n'a pas de choix : c'est son service.
            'services' => $estAgentService ? collect() : Service::orderBy('lib_service')->get(),
            'idService' => $idService,
            'service' => $estAgentService ? $request->user()->service : null,
            'layout' => Helper::layout(),
        ]);
    }

    public function exporter(Request $request)
    {
        $valide = $request->validate([
            'id_service' => 'nullable|integer|exists:services,id_service',
            'colonnes' => 'nullable|array',
            'colonnes.*' => 'string',
        ]);

        return Excel::download(
            new StocksExport($valide['id_service'] ?? null, $valide['colonnes'] ?? []),
            'Oryza_Stocks_'.now()->format('Y_m_d_His').'.xlsx',
        );
    }
}
