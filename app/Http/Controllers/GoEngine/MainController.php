<?php

namespace App\Http\Controllers\GoEngine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GoEngine;

class MainController extends Controller
{
    public function all()
    {
        return magicView("go_engines.all", [
            "engines" =>  GoEngine::all()->map(function ($item) {
                $item->enabled = $item->enabled ? "Aktif" : "Pasif";
                return $item;
            })
        ]);
    }
}
