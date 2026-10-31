<?php

namespace App\Http\Controllers;

use App\Support\Anfitrion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AccesoAnfitrionController extends Controller
{
    public function mostrar(): Response|RedirectResponse
    {
        if (Anfitrion::tieneAcceso()) {
            return redirect()->intended(route('cuestionarios.index'));
        }

        return Inertia::render('Anfitrion/Acceso');
    }

    public function entrar(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'clave' => ['required', 'string'],
        ]);

        if (! Anfitrion::claveValida($validado['clave'])) {
            return back()->withErrors([
                'clave' => 'Contraseña incorrecta.',
            ]);
        }

        Anfitrion::concederAcceso();

        return redirect()->intended(route('cuestionarios.index'));
    }
}
