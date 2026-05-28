<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateStudentRequest",
 *     type="object",
 *
 *     @OA\Property(
 *         property="career_id",
 *         type="integer",
 *         description="ID de la carrera",
 *         example=1
 *     ),
 *
 *     @OA\Property(
 *         property="group",
 *         type="string",
 *         description="Grupo del estudiante",
 *         example="A"
 *     ),
 *     @OA\Property(
 *         property="workshop",
 *         type="string",
 *         description="Taller asignado al estudiante",
 *         example="Dibujo"
 *     )
 * )
 */
class UpdateStudentRequest extends FormRequest
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
            'career_id' => ['sometimes','required','integer','exists:careers,id'],
            'group' => 'sometimes|required|string',
            'workshop' => 'sometimes|required|string'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'group' => strip_tags($this->group),
            'workshop' => strip_tags($this->workshop),
        ]);
    }

    public function messages(): array
    {
        return [
            'career_id.required' => 'El ID de la carrera es obligatorio.',
            'career_id.int' => 'El ID de la carrera debe ser un número entero.',
            'career_id.*.exists' => 'La carrera no existen en el sistema.',
            'group.required' => 'El grupo es obligatorio.',
            'workshop.required' => 'El taller es obligatorio.',
        ];
    }
}
