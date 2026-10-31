<?php

namespace App\Support;

final class NormasAmenhoot
{
    /**
     * @return array{titulo: string, cierre: string, reglas: list<array{titulo: string, texto: string}>}
     */
    public static function datos(): array
    {
        $ruta = resource_path('datos/normas-amenhoot.json');
        /** @var array{titulo: string, cierre: string, reglas: list<array{titulo: string, texto: string}>} $datos */
        $datos = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);

        return $datos;
    }

    /**
     * @return list<array{titulo: string, texto: string}>
     */
    public static function reglas(): array
    {
        return self::datos()['reglas'];
    }

    public static function textoNarracion(): string
    {
        $datos = self::datos();
        $partes = [$datos['titulo'].'.'];

        foreach ($datos['reglas'] as $indice => $regla) {
            $numero = $indice + 1;
            $partes[] = "{$numero}. {$regla['titulo']}. {$regla['texto']}";
        }

        $partes[] = $datos['cierre'];

        return implode(' ', $partes);
    }
}
