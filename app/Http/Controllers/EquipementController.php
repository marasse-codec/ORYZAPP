<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Utils\Helper;

class EquipementController extends Controller
{
    public function index()
    {
        $equipements = Equipement::where('en_service', true)->get();

        return view('equipements.index', [
            'layout' => Helper::layout(),
            'equipements' => $equipements
        ]);

    }

    public function create()
    {
        return view('equipements.create', [
            'layout' => Helper::layout(),
        ]);

    }
}
