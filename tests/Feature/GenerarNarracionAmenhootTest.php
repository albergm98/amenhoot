<?php

use App\Models\Cuestionario;
use App\Models\Pregunta;
use App\Models\User;
use App\Support\NormasAmenhoot;
use Illuminate\Support\Facades\Http;

test('normas amenhoot tienen texto de narración coherente', function () {
    $texto = NormasAmenhoot::textoNarracion();

    expect($texto)->toContain('Reglas del juego')
        ->and($texto)->toContain('Lee y elige')
        ->and($texto)->toContain('Podio final');
});

test('generar narración escribe wav cuando VoiceStudio responde', function () {
    $directorio = storage_path('framework/testing/amenhoot-audio');
    if (! is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    config([
        'amenhoot.voicestudio.url' => 'http://voicestudio.test',
        'amenhoot.voicestudio.voice' => 'demo',
        'amenhoot.voicestudio.model' => 'omnivoice',
        'amenhoot.voicestudio.timeout' => 5,
        'amenhoot.audio.directorio' => $directorio,
    ]);

    $wav = 'RIFF....WAVEfmt ';

    Http::fake([
        'http://voicestudio.test/v1/models' => Http::response(['data' => []], 200),
        'http://voicestudio.test/v1/audio/speech' => Http::response($wav, 200, [
            'Content-Type' => 'audio/wav',
        ]),
    ]);

    $usuario = User::factory()->create();
    $cuestionario = Cuestionario::query()->create([
        'user_id' => $usuario->id,
        'titulo' => 'Test narración',
        'descripcion' => null,
    ]);
    Pregunta::query()->create([
        'cuestionario_id' => $cuestionario->id,
        'enunciado' => '¿Pregunta de prueba?',
        'segundos_limite' => 20,
        'orden' => 0,
    ]);

    $this->artisan('amenhoot:generar-narracion', [
        '--cuestionario' => $cuestionario->id,
    ])->assertSuccessful();

    $carpetaPreguntas = $directorio.'/c'.$cuestionario->id;

    expect(file_exists($directorio.'/normas.wav'))->toBeTrue()
        ->and(file_exists($carpetaPreguntas.'/pregunta-0.wav'))->toBeTrue()
        ->and(file_get_contents($directorio.'/normas.wav'))->toBe($wav);

    foreach (glob($carpetaPreguntas.'/*') ?: [] as $archivo) {
        unlink($archivo);
    }
    rmdir($carpetaPreguntas);
    foreach (glob($directorio.'/*') ?: [] as $archivo) {
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
    rmdir($directorio);
});

test('generar narración falla si VoiceStudio no está disponible', function () {
    config([
        'amenhoot.voicestudio.url' => 'http://voicestudio-caido.test',
        'amenhoot.audio.directorio' => storage_path('framework/testing/amenhoot-audio-fail'),
    ]);

    Http::fake([
        'http://voicestudio-caido.test/*' => Http::response(null, 500),
    ]);

    $this->artisan('amenhoot:generar-narracion', ['--solo-normas' => true])
        ->assertFailed();
});
