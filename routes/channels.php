<?php

use App\Models\Partida;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('anfitrion.partida.{partidaId}', function ($user, int $partidaId) {
    $partida = Partida::query()->with('cuestionario')->find($partidaId);

    return $partida !== null && $partida->perteneceAlUsuario($user);
});
