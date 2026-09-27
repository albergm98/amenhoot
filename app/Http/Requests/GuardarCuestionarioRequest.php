<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarCuestionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'preguntas' => ['required', 'array', 'min:1'],
            'preguntas.*.enunciado' => ['required', 'string', 'max:500'],
            'preguntas.*.segundos_limite' => ['required', 'integer', 'min:5', 'max:120'],
            'preguntas.*.opciones' => ['required', 'array', 'size:4'],
            'preguntas.*.opciones.*.texto' => ['required', 'string', 'max:255'],
            'preguntas.*.opciones.*.es_correcta' => ['required', 'boolean'],
            'preguntas.*.indice_correcta' => ['required', 'integer', 'min:0', 'max:3'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validador): void {
            foreach ($this->input('preguntas', []) as $indice => $pregunta) {
                $opciones = $pregunta['opciones'] ?? [];
                $correctas = collect($opciones)->where('es_correcta', true)->count();

                if ($correctas !== 1) {
                    $validador->errors()->add(
                        "preguntas.{$indice}.opciones",
                        'Cada pregunta debe tener exactamente una opción correcta.'
                    );
                }
            }
        });
    }
}
