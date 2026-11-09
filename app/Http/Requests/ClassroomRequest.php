<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassroomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:500',
            'vacancies' => 'required|integer|min:1|max:500',
            'course_id' => 'required|integer|exists:courses,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O campo nome deve conter caracteres.',
            'name.min' => 'O campo nome deve ter no mínimo :min caracteres.',
            'name.max' => 'O campo nome deve ter no máximo :max caracteres.',
            'vacancies.required' => 'O campo vagas é obrigatório.',
            'vacancies.integer' => 'O campo vagas deve ser um número inteiro.',
            'vacancies.min' => 'O campo vagas deve ser no mínimo :min.',
            'vacancies.max' => 'O campo vagas deve ser no máximo :max.',
            'course_id.required' => 'Informe o curso da turma.',
            'course_id.integer' => 'O identificador do curso deve ser um número inteiro.',
            'course_id.exists' => 'O curso informado não existe.',
        ];
    }
}
