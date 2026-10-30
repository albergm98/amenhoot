<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use App\Support\Anfitrion;
use Illuminate\Database\Seeder;

final class AmenhootDiversionSeeder extends Seeder
{
    public function run(): void
    {
        $anfitrion = Anfitrion::usuario();

        $cuestionario = Cuestionario::query()->updateOrCreate(
            [
                'user_id' => $anfitrion->id,
                'titulo' => 'Cosas de la vida 🎉',
            ],
            [
                'descripcion' => '20 preguntas con trampa amable: de las que salen en la sobremesa, pero no tan obvias.',
            ],
        );

        $cuestionario->preguntas()->each(function ($pregunta): void {
            $pregunta->opciones()->delete();
            $pregunta->delete();
        });

        foreach ($this->preguntas() as $orden => $datos) {
            $pregunta = $cuestionario->preguntas()->create([
                'enunciado' => $datos['enunciado'],
                'segundos_limite' => $datos['segundos'] ?? 25,
                'orden' => $orden,
            ]);

            foreach ($datos['opciones'] as $ordenOpcion => $opcion) {
                $pregunta->opciones()->create([
                    'texto' => $opcion['texto'],
                    'es_correcta' => $opcion['correcta'],
                    'orden' => $ordenOpcion,
                ]);
            }
        }
    }

    /**
     * @return list<array{enunciado: string, segundos?: int, opciones: list<array{texto: string, correcta: bool}>}>
     */
    private function preguntas(): array
    {
        return [
            [
                'enunciado' => '¿En qué año se estrenó “Titanic” en cines?',
                'opciones' => [
                    ['texto' => '1997', 'correcta' => true],
                    ['texto' => '1994', 'correcta' => false],
                    ['texto' => '1999', 'correcta' => false],
                    ['texto' => '2001', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Cuál es el ingrediente que NO lleva una margarita clásica italiana?',
                'opciones' => [
                    ['texto' => 'Piña', 'correcta' => true],
                    ['texto' => 'Tomate', 'correcta' => false],
                    ['texto' => 'Mozzarella', 'correcta' => false],
                    ['texto' => 'Albahaca', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué casa de Hogwarts tiene un león en su escudo?',
                'opciones' => [
                    ['texto' => 'Gryffindor', 'correcta' => true],
                    ['texto' => 'Slytherin', 'correcta' => false],
                    ['texto' => 'Ravenclaw', 'correcta' => false],
                    ['texto' => 'Hufflepuff', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿En qué ciudad española está la Sagrada Familia?',
                'opciones' => [
                    ['texto' => 'Barcelona', 'correcta' => true],
                    ['texto' => 'Madrid', 'correcta' => false],
                    ['texto' => 'Valencia', 'correcta' => false],
                    ['texto' => 'Sevilla', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué planeta del sistema solar gira “tumbado” (eje muy inclinado)?',
                'opciones' => [
                    ['texto' => 'Urano', 'correcta' => true],
                    ['texto' => 'Marte', 'correcta' => false],
                    ['texto' => 'Venus', 'correcta' => false],
                    ['texto' => 'Neptuno', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Quién pintó “La noche estrellada”?',
                'opciones' => [
                    ['texto' => 'Van Gogh', 'correcta' => true],
                    ['texto' => 'Picasso', 'correcta' => false],
                    ['texto' => 'Dalí', 'correcta' => false],
                    ['texto' => 'Monet', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'En WhatsApp, ¿qué indican los dos ticks AZULES?',
                'opciones' => [
                    ['texto' => 'Que lo ha leído (si tiene confirmación)', 'correcta' => true],
                    ['texto' => 'Que solo llegó al servidor', 'correcta' => false],
                    ['texto' => 'Que está escribiendo', 'correcta' => false],
                    ['texto' => 'Que lo reenvió', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Cuál es la capital de Australia?',
                'opciones' => [
                    ['texto' => 'Canberra', 'correcta' => true],
                    ['texto' => 'Sídney', 'correcta' => false],
                    ['texto' => 'Melbourne', 'correcta' => false],
                    ['texto' => 'Brisbane', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué vitaminas se asocian sobre todo a la naranja?',
                'opciones' => [
                    ['texto' => 'Vitamina C', 'correcta' => true],
                    ['texto' => 'Vitamina D', 'correcta' => false],
                    ['texto' => 'Vitamina B12', 'correcta' => false],
                    ['texto' => 'Vitamina K', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿En qué serie sale el lema “Winter is coming”?',
                'opciones' => [
                    ['texto' => 'Juego de Tronos', 'correcta' => true],
                    ['texto' => 'The Witcher', 'correcta' => false],
                    ['texto' => 'Vikingos', 'correcta' => false],
                    ['texto' => 'The Last of Us', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué actor interpreta a Jack Sparrow?',
                'opciones' => [
                    ['texto' => 'Johnny Depp', 'correcta' => true],
                    ['texto' => 'Orlando Bloom', 'correcta' => false],
                    ['texto' => 'Brad Pitt', 'correcta' => false],
                    ['texto' => 'Tom Cruise', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué animal pone el huevo más grande del mundo actual?',
                'opciones' => [
                    ['texto' => 'El avestruz', 'correcta' => true],
                    ['texto' => 'El águila', 'correcta' => false],
                    ['texto' => 'El pingüino emperador', 'correcta' => false],
                    ['texto' => 'El cisne', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿En qué ciudad se inventó (según la tradición) la pizza?',
                'opciones' => [
                    ['texto' => 'Nápoles', 'correcta' => true],
                    ['texto' => 'Roma', 'correcta' => false],
                    ['texto' => 'Milán', 'correcta' => false],
                    ['texto' => 'Florencia', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué red social nació primero?',
                'opciones' => [
                    ['texto' => 'Facebook', 'correcta' => true],
                    ['texto' => 'Instagram', 'correcta' => false],
                    ['texto' => 'TikTok', 'correcta' => false],
                    ['texto' => 'Tuenti', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Cuántos minutos “oficiales” dura un partido de baloncesto NBA (sin prórrogas)?',
                'opciones' => [
                    ['texto' => '48', 'correcta' => true],
                    ['texto' => '40', 'correcta' => false],
                    ['texto' => '45', 'correcta' => false],
                    ['texto' => '60', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué metal es líquido a temperatura ambiente?',
                'opciones' => [
                    ['texto' => 'Mercurio', 'correcta' => true],
                    ['texto' => 'Hierro', 'correcta' => false],
                    ['texto' => 'Aluminio', 'correcta' => false],
                    ['texto' => 'Cobre', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Quién canta el tema original de “Let It Be”?',
                'opciones' => [
                    ['texto' => 'The Beatles', 'correcta' => true],
                    ['texto' => 'Queen', 'correcta' => false],
                    ['texto' => 'ABBA', 'correcta' => false],
                    ['texto' => 'The Rolling Stones', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Cuál es el océano más profundo (fosa de las Marianas)?',
                'opciones' => [
                    ['texto' => 'Pacífico', 'correcta' => true],
                    ['texto' => 'Atlántico', 'correcta' => false],
                    ['texto' => 'Índico', 'correcta' => false],
                    ['texto' => 'Ártico', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'En café, ¿qué es un “americano”?',
                'opciones' => [
                    ['texto' => 'Espresso alargado con agua caliente', 'correcta' => true],
                    ['texto' => 'Solo leche espumada', 'correcta' => false],
                    ['texto' => 'Café con chocolate', 'correcta' => false],
                    ['texto' => 'Descafeinado con hielo', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué película popularizó la frase “Yo soy tu padre”?',
                'opciones' => [
                    ['texto' => 'El Imperio Contraataca', 'correcta' => true],
                    ['texto' => 'Una nueva esperanza', 'correcta' => false],
                    ['texto' => 'El retorno del Jedi', 'correcta' => false],
                    ['texto' => 'La amenaza fantasma', 'correcta' => false],
                ],
            ],
        ];
    }
}
