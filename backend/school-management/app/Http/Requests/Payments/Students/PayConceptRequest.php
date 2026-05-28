<?php

namespace App\Http\Requests\Payments\Students;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="PayConceptRequest",
 *     type="object",
 *     @OA\Property(
 *         property="concept_id",
 *         type="integer",
 *         description="ID del concepto que se desea pagar",
 *         example=123
 *     )
 * )
 */

class PayConceptRequest extends FormRequest
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
            'concept_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'concept_id.required' => 'Debes proporcionar un ID de concepto.',
            'concept_id.integer' => 'El ID de concepto debe ser un número entero.',
            'concept_id.exists' => 'El concepto proporcionado no existe.',
        ];
    }
}
