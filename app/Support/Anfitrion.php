<?php

namespace App\Support;

use App\Models\User;

final class Anfitrion
{
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
}
