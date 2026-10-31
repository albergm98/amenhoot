<?php

namespace App\Support;

use App\Models\User;

final class Anfitrion
{
    public const SESION_ACCESO = 'amenhoot_anfitrion_ok';

    public static function usuario(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'anfitrion@stellar.test'],
            [
                'name' => 'Anfitrión',
                'password' => 'password',
            ],
        );
    }

    public static function tieneAcceso(): bool
    {
        return (bool) session(self::SESION_ACCESO);
    }

    public static function concederAcceso(): void
    {
        session([self::SESION_ACCESO => true]);
    }

    public static function claveValida(string $clave): bool
    {
        return hash_equals((string) config('amenhoot.clave_anfitrion'), $clave);
    }
}
