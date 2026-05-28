<?php

namespace App\Http\Requests\General;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ImportRequest",
 *     type="object",
 *     @OA\Property(
 *         property="file",
 *         type="string",
 *         format="binary",
 *         description="Archivo con los usuarios a importar (XLSX, XLS o CSV)"
 *     )
 * )
 */
class ImportRequest extends FormRequest
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
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ];
    }

     public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file'     => 'Debe proporcionar un archivo válido.',
            'file.mimes'    => 'El archivo debe ser de tipo XLSX, XLS o CSV.',
        ];
    }
}
