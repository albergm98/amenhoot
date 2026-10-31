<?php

use App\Support\CatalogoPersonas;
use Illuminate\Support\Facades\Http;

test('generar nombres bingo escribe wav por persona', function () {
    $directorio = storage_path('framework/testing/bingo-audio');
    if (! is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    config([
        'amenhoot.voicestudio.url' => 'http://voicestudio.test',
        'amenhoot.voicestudio.voice' => 'demo',
        'amenhoot.voicestudio.model' => 'omnivoice',
        'amenhoot.voicestudio.timeout' => 5,
        'bingo.audio.directorio' => $directorio,
    ]);

    $wav = 'RIFF....WAVEfmt ';
    Http::fake([
        'http://voicestudio.test/v1/models' => Http::response(['data' => []], 200),
        'http://voicestudio.test/v1/audio/speech' => Http::response($wav, 200, [
            'Content-Type' => 'audio/wav',
        ]),
    ]);

    $this->artisan('bingo:generar-nombres', [
        '--solo' => ['sara', 'edu'],
    ])->assertSuccessful();

    expect(file_exists($directorio.'/sara.wav'))->toBeTrue()
        ->and(file_exists($directorio.'/edu.wav'))->toBeTrue()
        ->and(file_get_contents($directorio.'/sara.wav'))->toBe($wav)
        ->and(CatalogoPersonas::todas())->not->toBeEmpty();

    foreach (glob($directorio.'/*') ?: [] as $archivo) {
        unlink($archivo);
    }
    rmdir($directorio);
});

test('generar nombres bingo falla sin VoiceStudio', function () {
    config([
        'amenhoot.voicestudio.url' => 'http://voicestudio-caido.test',
        'bingo.audio.directorio' => storage_path('framework/testing/bingo-audio-fail'),
    ]);

    Http::fake([
        'http://voicestudio-caido.test/*' => Http::response(null, 500),
    ]);

    $this->artisan('bingo:generar-nombres')->assertFailed();
});
