<?php

namespace App\Http\Controllers;

use App\Support\CatalogoBingo;
use Inertia\Inertia;
use Inertia\Response;

final class BingoController extends Controller
{
    public function sala(?int $codigo = null): Response
    {
        return Inertia::render('Bingo/Sala', [
            'imagenes' => CatalogoBingo::imagenes(),
            'prioridad' => $codigo === 1 ? CatalogoBingo::secuenciaAmanada() : [],
        ]);
    }
}
