<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'apodo' => ['required', 'string', 'min:2', 'max:20'],
            'equipo_id' => ['nullable', 'integer', 'exists:equipos,id'],
        ];
    }
}
