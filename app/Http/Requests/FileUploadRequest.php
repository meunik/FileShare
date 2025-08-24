<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:' . (50 * 1024 * 1024), // 50GB em KB
                function ($attribute, $value, $fail) {
                    // Validações de segurança adicionais
                    $dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'jsp'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    
                    if (in_array($extension, $dangerousExtensions)) {
                        $fail('Tipo de arquivo não permitido por motivos de segurança.');
                    }
                    
                    // Valida nome do arquivo para evitar path traversal
                    $filename = $value->getClientOriginalName();
                    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                        $fail('Nome do arquivo contém caracteres não permitidos.');
                    }
                }
            ],
            'duration' => [
                'required',
                'integer',
                'min:1'
            ],
            'unit' => [
                'required',
                'in:second,minute,hour'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Por favor, selecione um arquivo.',
            'file.file' => 'O arquivo enviado não é válido.',
            'file.max' => 'O arquivo não pode ser maior que 50GB.',
            'duration.required' => 'O tempo de duração é obrigatório.',
            'duration.integer' => 'O tempo de duração deve ser um número inteiro.',
            'duration.min' => 'O tempo de duração deve ser pelo menos 1.',
            'duration.max' => 'O tempo de duração não pode exceder 24 horas.',
            'unit.required' => 'A unidade de tempo é obrigatória.',
            'unit.in' => 'A unidade de tempo deve ser segundo, minuto ou hora.',
        ];
    }
}
