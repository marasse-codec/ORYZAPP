<?php

namespace App\Http\Controllers;

use App\Models\Appui;
use App\Models\TypeAppui;
use App\Models\UniteAppui;
use App\Utils\Helper;
use Illuminate\Http\Request;

class AppuiController extends Controller
{
    public function index()
    {
        return view('appuis.index', [
            'appuis' => Appui::with('uniteAppui')->orderBy('libelle')->get(),
            'layout' => Helper::layout(),
        ]);
    }

    public function create()
    {
        return view('appuis.create', [
            'typesAppuis' => TypeAppui::with('service')->orderBy('libelle')->get(),
            'layout' => Helper::layout(),
            'unitesAppui' => UniteAppui::orderBy('lib_unite_appui')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $valide = $request->validate([
            'libelle' => 'required|string|max:255',
            'id_type_appui' => 'required|exists:types_appuis,id_type_appui',
            'quantite_en_stock' => 'nullable|integer|min:0',
            'cout_unitaire' => 'nullable|numeric|min:0',
            'id_unite_appui' => 'nullable|exists:unites_appuis,id_unite_appui',
        ]);

        Appui::create($valide + ['quantite_en_stock' => $valide['quantite_en_stock'] ?? 0]);

        return redirect()->route('appuis.index')->with('success', 'Appui enregistré avec succès.');
    }
}
