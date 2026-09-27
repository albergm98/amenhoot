<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearPartidaRequest extends FormRequest
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
            'modo_equipos' => ['sometimes', 'boolean'],
            'equipos' => ['required_if:modo_equipos,true', 'array', 'min:2', 'max:6'],
            'equipos.*.nombre' => ['required_with:equipos', 'string', 'max:40'],
            'equipos.*.color' => ['required_with:equipos', 'string', 'max:32'],
        ];
    }
}
