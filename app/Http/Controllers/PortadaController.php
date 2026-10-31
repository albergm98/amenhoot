<?php

namespace App\Http\Controllers;

use App\Support\CatalogoBingo;
use Inertia\Inertia;
use Inertia\Response;

final class PortadaController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'fotosCollage' => CatalogoBingo::muestraCollage(30),
        ]);
    }
}
