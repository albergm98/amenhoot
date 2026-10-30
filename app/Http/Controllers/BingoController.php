<?php

namespace App\Http\Controllers;

use App\Support\CatalogoBingo;
use Inertia\Inertia;
use Inertia\Response;

final class BingoController extends Controller
{
    public function sala(): Response
    {
        return Inertia::render('Bingo/Sala', [
            'imagenes' => CatalogoBingo::imagenes(),
        ]);
    }
}
