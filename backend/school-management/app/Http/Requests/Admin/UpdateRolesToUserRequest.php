<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateRolesToUserRequest",
 *     type="object",
 *     required={"curps"},
 *
 *     @OA\Property(
 *         property="rolesToAdd",
 *         type="array",
 *         description="Array de nombres de roles a agregar",
 *         @OA\Items(
 *             type="string",
 *             description="Nombre de un rol existente",
 *             example="editor"
 *         ),
 *         example={"editor", "supervisor"}
 *     ),
 *     @OA\Property(
 *         property="rolesToRemove",
 *         type="array",
 *         description="Array de nombres de roles a remover",
 *         @OA\Items(
 *             type="string",
 *             description="Nombre de un rol existente",
 *             example="viewer"
 *         ),
 *         example={"viewer", "assistant"}
 *     )
 * )
 */
class UpdateRolesToUserRequest extends FormRequest
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

            'rolesToAdd' => ['sometimes', 'required', 'array'],
            'rolesToAdd.*' => ['string', 'exists:roles,name'],

            'rolesToRemove' => ['sometimes', 'required', 'array'],
            'rolesToRemove.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [

            'rolesToAdd.array' => 'rolesToAdd debe ser un array.',
            'rolesToAdd.*.exists' => 'Uno o más roles a agregar no existen.',

            'rolesToRemove.array' => 'rolesToRemove debe ser un array.',
            'rolesToRemove.*.exists' => 'Uno o más roles a remover no existen.',
        ];
    }
}
