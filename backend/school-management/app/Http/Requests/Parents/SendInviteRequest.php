<?php

namespace App\Http\Requests\Parents;

use Illuminate\Foundation\Http\FormRequest;


/**
 * @OA\Schema(
 *     schema="SendInviteRequest",
 *     type="object",
 *     required={"student_id","parent_email"},
 *
 *     @OA\Property(
 *         property="student_id",
 *         type="integer",
 *         description="ID del estudiante existente",
 *         example=42
 *     ),
 *
 *     @OA\Property(
 *         property="parent_email",
 *         type="string",
 *         format="email",
 *         description="Correo electrónico del padre",
 *         example="parent@example.com"
 *     )
 * )
 */

class SendInviteRequest extends FormRequest
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
            'student_id'   => 'required|integer|exists:users,id',
            'parent_email' => 'required|email',
        ];
    }

     public function messages(): array
    {
        return [
            'student_id.required' => 'El ID del estudiante es obligatorio.',
            'student_id.integer'  => 'El ID del estudiante debe ser un número.',
            'student_id.exists'   => 'No se encontró un estudiante con este ID.',
            'parent_email.required' => 'El correo del padre es obligatorio.',
            'parent_email.email'    => 'Debe proporcionar un correo válido.',
        ];
    }
}
