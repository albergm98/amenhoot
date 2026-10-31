<?php

namespace App\Http\Middleware;

use App\Support\Anfitrion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerificarAccesoAnfitrion
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Anfitrion::tieneAcceso()) {
            return $next($request);
        }

        return redirect()->guest(route('anfitrion.acceso'));
    }
}
