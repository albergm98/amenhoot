<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

final class CatalogoBingo
{
    private const EXTENSIONES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * @return list<array{id: int, url: string, columna: string, etiqueta: string}>
     */
    public static function imagenes(): array
    {
        $carpeta = public_path('assets/img/bingo');

        if (! File::isDirectory($carpeta)) {
            return [];
        }

        return collect(File::files($carpeta))
            ->filter(fn (SplFileInfo $archivo) => in_array(strtolower($archivo->getExtension()), self::EXTENSIONES, true))
            ->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                $columna = self::ordenColumna($a->getFilename()) <=> self::ordenColumna($b->getFilename());

                if ($columna !== 0) {
                    return $columna;
                }

                return strnatcasecmp($a->getFilename(), $b->getFilename());
            })
            ->values()
            ->map(function (SplFileInfo $archivo, int $indice): array {
                $clave = self::claveColumna($archivo->getFilename());

                return [
                    'id' => $indice + 1,
                    'url' => '/assets/img/bingo/'.rawurlencode($archivo->getFilename()),
                    'columna' => $clave,
                    'etiqueta' => self::etiquetaColumna($clave),
                ];
            })
            ->all();
    }

    /**
     * Fotos que salen primero en /bingo/1 (barajadas entre sí; el resto después al azar).
     *
     * @return list<string> nombres de archivo sin carpeta
     */
    public static function secuenciaAmanada(): array
    {
        return [
            'paula10.jpeg',
            'sara4.jpeg',
            'alberto5.jpeg',
            'alberto6.jpeg',
            'pablo6.jpeg',
            'joseja10.jpeg',
            'edu10.jpeg',
            'edu7.jpeg',
            'eva8.jpeg',
            'eva1.jpeg',
            'manu1.jpeg',
            'manu7.jpeg',
            'luis6.jpeg',
            'luis10.jpeg',
        ];
    }

    /**
     * Muestra para el collage de la portada: una foto por persona y relleno del resto.
     *
     * @return list<string>
     */
    public static function muestraCollage(int $cantidad = 30): array
    {
        $imagenes = collect(self::imagenes());
        if ($imagenes->isEmpty()) {
            return [];
        }

        $porPersona = $imagenes
            ->filter(fn (array $ficha): bool => preg_match('/^[\p{L}]+$/u', $ficha['columna']) === 1)
            ->groupBy('columna')
            ->map(fn ($fichas) => $fichas->first()['url']);

        $elegidas = $porPersona->values();
        $restantes = $imagenes
            ->pluck('url')
            ->reject(fn (string $url) => $elegidas->contains($url))
            ->shuffle()
            ->values();

        return $elegidas
            ->concat($restantes)
            ->take(max(1, $cantidad))
            ->values()
            ->all();
    }

    private static function claveColumna(string $nombre): string
    {
        $base = pathinfo($nombre, PATHINFO_FILENAME);

        if (preg_match('/^([\p{L}]+)(\d+)$/u', $base, $coincidencias) === 1) {
            return Str::lower($coincidencias[1]);
        }

        if (preg_match('/^(WhatsApp Image .+ at \d{2}\.\d{2}\.\d{2})(?: \(\d+\))?$/u', $base, $coincidencias) === 1) {
            return $coincidencias[1];
        }

        return $base;
    }

    private static function etiquetaColumna(string $clave): string
    {
        if (preg_match('/^[\p{L}]+$/u', $clave) === 1) {
            return Str::ucfirst($clave);
        }

        if (preg_match('/at (\d{2})\.(\d{2})\.(\d{2})$/', $clave, $coincidencias) === 1) {
            return $coincidencias[1].':'.$coincidencias[2];
        }

        return $clave;
    }

    private static function ordenColumna(string $nombre): string
    {
        $clave = self::claveColumna($nombre);

        return (preg_match('/^[\p{L}]+$/u', $clave) === 1 ? '0-' : '1-').$clave;
    }
}
