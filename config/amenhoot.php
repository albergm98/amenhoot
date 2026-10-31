<?php

return [
    'clave_anfitrion' => env('AMENHOOT_CLAVE_ANFITRION', '000001'),
    'voicestudio' => [
        'url' => env('VOICESTUDIO_URL', 'http://127.0.0.1:3900'),
        'voice' => env('VOICESTUDIO_VOICE', 'default'),
        'model' => env('VOICESTUDIO_MODEL', 'omnivoice'),
        'timeout' => (int) env('VOICESTUDIO_TIMEOUT', 180),
    ],
    'audio' => [
        'directorio' => public_path('assets/audio/amenhoot'),
        'url_base' => '/assets/audio/amenhoot',
    ],
];
