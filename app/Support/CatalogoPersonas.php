<?php

namespace App\Support;

/**
 * Las 10 personas del bingo (columnas con nombre) para Amenhoot:
 * al unirse se elige una y se reutiliza una foto concreta del catálogo.
 */
final class CatalogoPersonas
{
    /** @var array<string, string> archivo preferido por persona (sin carpeta) */
    private const FOTOS = [
        'alberto' => 'alberto3.jpeg',
        'angie' => 'angie4.jpeg',
        'joseja' => 'joseja3.jpeg',
        'luis' => 'luis10.jpeg',
        'manu' => 'manu9.jpeg',
        'pablo' => 'pablo6.jpeg',
        'paula' => 'paula6.jpeg',
        'sara' => 'sara6.jpeg',
    ];

    /**
     * @return list<array{clave: string, nombre: string, foto: string}>
     */
    public static function todas(): array
    {
        return collect(CatalogoBingo::imagenes())
            ->filter(fn (array $ficha): bool => preg_match('/^[\p{L}]+$/u', $ficha['columna']) === 1)
            ->groupBy('columna')
            ->map(function ($fichas, string $clave): array {
                $preferida = self::FOTOS[$clave] ?? null;
                $foto = $preferida === null
                    ? null
                    : $fichas->first(fn (array $ficha): bool => str_ends_with(urldecode($ficha['url']), '/'.$preferida));

                $foto ??= $fichas->first();

                return [
                    'clave' => $clave,
                    'nombre' => $foto['etiqueta'],
                    'foto' => $foto['url'],
                ];
            })
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @return array{clave: string, nombre: string, foto: string}|null
     */
    public static function porClave(string $clave): ?array
    {
        $buscada = mb_strtolower($clave);

        return collect(self::todas())->first(
            fn (array $persona): bool => $persona['clave'] === $buscada,
        );
    }

    /**
     * @return list<string>
     */
    public static function claves(): array
    {
        return collect(self::todas())->pluck('clave')->all();
    }
}
