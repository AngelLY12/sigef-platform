<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ChangeUserStatusRequest",
 *     type="object",
 *     required={"ids"},
 *     @OA\Property(
 *         property="ids",
 *         type="array",
 *         description="Array de IDs de los usuarios a cambiar de estado",
 *         example={1,2,3},
 *         @OA\Items(
 *             type="integer",
 *             description="ID de un usuario existente",
 *             example=1
 *         )
 *     )
 * )
 */

class ChangeUserStatusRequest extends FormRequest
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
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debes proporcionar un array de IDs.',
            'ids.array' => 'El campo ids debe ser un array.',
            'ids.*.exists' => 'Uno o más IDs no existen en el sistema.',
        ];
    }
}
