<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action' => ['required', Rule::in(['attach', 'detach'])],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Informe o usuário.',
            'user_id.integer' => 'O identificador do usuário deve ser um número inteiro.',
            'user_id.exists' => 'O usuário informado não existe.',
            'action.required' => 'Informe a ação attach ou detach.',
            'action.in' => 'A ação deve ser attach (incluir) ou detach (remover).',
        ];
    }
}
