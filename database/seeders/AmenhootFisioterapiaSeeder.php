<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use App\Support\Anfitrion;
use Illuminate\Database\Seeder;

final class AmenhootFisioterapiaSeeder extends Seeder
{
    public function run(): void
    {
        $anfitrion = Anfitrion::usuario();

        $cuestionario = Cuestionario::query()->updateOrCreate(
            [
                'user_id' => $anfitrion->id,
                'titulo' => 'Fisioterapia 💪',
            ],
            [
                'descripcion' => '20 preguntas de conocimientos de fisioterapia: anatomía, patología y tratamiento.',
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

            $opciones = collect($datos['opciones'])->shuffle()->values();
            foreach ($opciones as $ordenOpcion => $opcion) {
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
                'enunciado' => '¿Cuántas vértebras cervicales tiene normalmente el ser humano?',
                'opciones' => [
                    ['texto' => '7', 'correcta' => true],
                    ['texto' => '5', 'correcta' => false],
                    ['texto' => '12', 'correcta' => false],
                    ['texto' => '10', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué músculo es el principal flexor de cadera?',
                'opciones' => [
                    ['texto' => 'Iliopsoas', 'correcta' => true],
                    ['texto' => 'Glúteo mayor', 'correcta' => false],
                    ['texto' => 'Bíceps femoral', 'correcta' => false],
                    ['texto' => 'Sóleo', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'En la escala de fuerza muscular de Daniels, ¿qué nota indica contracción visible sin movimiento?',
                'opciones' => [
                    ['texto' => '1', 'correcta' => true],
                    ['texto' => '0', 'correcta' => false],
                    ['texto' => '3', 'correcta' => false],
                    ['texto' => '5', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué nervio se comprime con más frecuencia en el síndrome del túnel carpiano?',
                'opciones' => [
                    ['texto' => 'Mediano', 'correcta' => true],
                    ['texto' => 'Cubital', 'correcta' => false],
                    ['texto' => 'Radial', 'correcta' => false],
                    ['texto' => 'Axilar', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Cuál es el movimiento principal del músculo deltoides medio?',
                'opciones' => [
                    ['texto' => 'Abducción de hombro', 'correcta' => true],
                    ['texto' => 'Aducción de hombro', 'correcta' => false],
                    ['texto' => 'Flexión de codo', 'correcta' => false],
                    ['texto' => 'Extensión de muñeca', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'La artrosis se caracteriza sobre todo por el desgaste de…',
                'opciones' => [
                    ['texto' => 'Cartílago articular', 'correcta' => true],
                    ['texto' => 'Médula ósea', 'correcta' => false],
                    ['texto' => 'Discos intervertebrales únicamente', 'correcta' => false],
                    ['texto' => 'Tendones flexores', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué prueba clásica ayuda a valorar el ligamento cruzado anterior (LCA)?',
                'opciones' => [
                    ['texto' => 'Lachman / cajón anterior', 'correcta' => true],
                    ['texto' => 'Thompson', 'correcta' => false],
                    ['texto' => 'Phalen', 'correcta' => false],
                    ['texto' => 'Adson', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'En una lesión aguda (primeras 48–72 h), ¿qué suele recomendarse evitar de forma agresiva?',
                'opciones' => [
                    ['texto' => 'Calor intenso local', 'correcta' => true],
                    ['texto' => 'Elevación del miembro', 'correcta' => false],
                    ['texto' => 'Reposo relativo', 'correcta' => false],
                    ['texto' => 'Compresión suave', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué músculo es el principal extensor de rodilla?',
                'opciones' => [
                    ['texto' => 'Cuádriceps', 'correcta' => true],
                    ['texto' => 'Isquiotibiales', 'correcta' => false],
                    ['texto' => 'Gastrocnemio', 'correcta' => false],
                    ['texto' => 'Tibial anterior', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'El plano sagital divide el cuerpo en…',
                'opciones' => [
                    ['texto' => 'Derecha e izquierda', 'correcta' => true],
                    ['texto' => 'Anterior y posterior', 'correcta' => false],
                    ['texto' => 'Superior e inferior', 'correcta' => false],
                    ['texto' => 'Proximal y distal', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué estructura une músculo con hueso?',
                'opciones' => [
                    ['texto' => 'Tendón', 'correcta' => true],
                    ['texto' => 'Ligamento', 'correcta' => false],
                    ['texto' => 'Bursa', 'correcta' => false],
                    ['texto' => 'Menisco', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'En fisioterapia respiratoria, ¿qué dispositivo ayuda a mejorar la higiene bronquial con oscilaciones?',
                'opciones' => [
                    ['texto' => 'Flutter / Acapella', 'correcta' => true],
                    ['texto' => 'Goniómetro', 'correcta' => false],
                    ['texto' => 'Dinamómetro', 'correcta' => false],
                    ['texto' => 'Espéculo', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Cuál es la unidad funcional del músculo esquelético?',
                'opciones' => [
                    ['texto' => 'Sarcómero', 'correcta' => true],
                    ['texto' => 'Osteona', 'correcta' => false],
                    ['texto' => 'Nefrona', 'correcta' => false],
                    ['texto' => 'Alvéolo', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'Una contractura muscular es, en esencia…',
                'opciones' => [
                    ['texto' => 'Una contracción involuntaria sostenida', 'correcta' => true],
                    ['texto' => 'Una rotura completa del tendón', 'correcta' => false],
                    ['texto' => 'Una fractura por estrés', 'correcta' => false],
                    ['texto' => 'Una infección articular', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué movimiento realiza principalmente el tibial anterior?',
                'opciones' => [
                    ['texto' => 'Dorsiflexión del tobillo', 'correcta' => true],
                    ['texto' => 'Flexión plantar', 'correcta' => false],
                    ['texto' => 'Extensión de rodilla', 'correcta' => false],
                    ['texto' => 'Rotación externa de cadera', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'En electroterapia TENS, el objetivo principal suele ser…',
                'opciones' => [
                    ['texto' => 'Alivio del dolor', 'correcta' => true],
                    ['texto' => 'Soldar fracturas', 'correcta' => false],
                    ['texto' => 'Medir la tensión arterial', 'correcta' => false],
                    ['texto' => 'Diagnosticar hernias', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué ligamento estabiliza sobre todo el lado medial de la rodilla?',
                'opciones' => [
                    ['texto' => 'Ligamento colateral medial (LCM)', 'correcta' => true],
                    ['texto' => 'Ligamento cruzado posterior', 'correcta' => false],
                    ['texto' => 'Ligamento colateral lateral', 'correcta' => false],
                    ['texto' => 'Ligamento anular', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'La escoliosis se define como una desviación de la columna en el plano…',
                'opciones' => [
                    ['texto' => 'Frontal (con rotación vertebral)', 'correcta' => true],
                    ['texto' => 'Solo sagital', 'correcta' => false],
                    ['texto' => 'Horizontal puro sin curva', 'correcta' => false],
                    ['texto' => 'Ninguno: es solo muscular', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => '¿Qué músculo forma parte del manguito de los rotadores?',
                'opciones' => [
                    ['texto' => 'Supraespinoso', 'correcta' => true],
                    ['texto' => 'Pectoral mayor', 'correcta' => false],
                    ['texto' => 'Trapecio inferior', 'correcta' => false],
                    ['texto' => 'Serrato anterior', 'correcta' => false],
                ],
            ],
            [
                'enunciado' => 'Tras una artroplastia total de cadera (vía posterior clásica), ¿qué movimiento suele restringirse al inicio?',
                'opciones' => [
                    ['texto' => 'Flexión + aducción + rotación interna combinadas', 'correcta' => true],
                    ['texto' => 'Caminar con andador', 'correcta' => false],
                    ['texto' => 'Respirar profundamente', 'correcta' => false],
                    ['texto' => 'Extensión de codo', 'correcta' => false],
                ],
            ],
        ];
    }
}
