<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AttachStudentRequest",
 *     type="object",
 *     required={"user_id","career_id","n_control","semestre","group","workshop"},
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="ID del usuario",
 *         example=4
 *     ),
 *     @OA\Property(
 *         property="career_id",
 *         type="integer",
 *         description="ID de la carrera",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="n_control",
 *         type="string",
 *         description="Número de control del estudiante",
 *         example="2578900"
 *     ),
 *     @OA\Property(
 *         property="semestre",
 *         type="integer",
 *         description="Semestre actual del estudiante",
 *         example=1
 *     ),
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
class AttachStudentRequest extends FormRequest
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
            'user_id' => 'required|int',
            'user_id.*' => ['exists:users,id'],
            'career_id' => 'required|int',
            'career_id.*' => ['exists:careers,id'],
            'n_control' => 'required|string',
            'semestre' => 'required|int',
            'group' => 'required|string',
            'workshop' => 'required|string'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'n_control' => strip_tags($this->n_control),
            'group' => strip_tags($this->group),
            'workshop' => strip_tags($this->workshop),
        ]);
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El ID del usuario es obligatorio.',
            'user_id.int' => 'El ID del usuario debe ser un número entero.',
            'user_id.*.exists' => 'El usuario no existe en el sistema',
            'career_id.required' => 'El ID de la carrera es obligatorio.',
            'career_id.int' => 'El ID de la carrera debe ser un número entero.',
            'career_id.*.exists' => 'El ID de la carrera no existe',
            'n_control.required' => 'El número de control es obligatorio.',
            'semestre.required' => 'El semestre es obligatorio.',
            'semestre.int' => 'El semestre debe ser un número entero.',
            'group.required' => 'El grupo es obligatorio.',
            'workshop.required' => 'El taller es obligatorio.',
        ];
    }
}
