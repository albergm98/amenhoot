<?php

namespace App\Estados;

enum EstadoPartida: string
{
    case EsperandoJugadores = 'esperando_jugadores';
    case MostrandoPregunta = 'mostrando_pregunta';
    case MostrandoResultados = 'mostrando_resultados';
    case Finalizada = 'finalizada';
}
