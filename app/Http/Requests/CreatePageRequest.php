<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePageRequest extends FormRequest
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
            'identifier' => 'required|string|max:255',
            'password' => 'nullable|string|min:1|max:100',
            'duration' => 'required_with:password|integer|min:1',
            'unit' => 'required_with:password|in:second,minute,hour',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'O identificador é obrigatório.',
            'identifier.max' => 'O identificador não pode ter mais de 255 caracteres.',
            'password.min' => 'A senha deve ter pelo menos 1 caractere.',
            'password.max' => 'A senha não pode ter mais de 100 caracteres.',
            'duration.required_with' => 'A duração é obrigatória quando há senha.',
            'duration.min' => 'A duração deve ser pelo menos 1.',
            'unit.required_with' => 'A unidade de tempo é obrigatória quando há senha.',
            'unit.in' => 'Unidade de tempo inválida.',
        ];
    }
}
