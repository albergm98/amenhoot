<?php

namespace App\Http\Requests;

use App\Support\CatalogoPersonas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnirsePartidaRequest extends FormRequest
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
            'pin' => ['required', 'string', 'size:6'],
            'persona' => ['required', 'string', Rule::in(CatalogoPersonas::claves())],
            'equipo_id' => ['nullable', 'integer', 'exists:equipos,id'],
        ];
    }
}
