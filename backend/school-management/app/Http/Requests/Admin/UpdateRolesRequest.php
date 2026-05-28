<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateRolesRequest",
 *     type="object",
 *     required={"curps"},
 *     @OA\Property(
 *         property="curps",
 *         type="array",
 *         description="Array de CURPs de los usuarios a actualizar roles",
 *         @OA\Items(
 *             type="string",
 *             description="CURP de un usuario existente",
 *             example="GODE561231HDFABC09"
 *         ),
 *         example={"GODE561231HDFABC09", "PEMJ800101MDFLRS08"}
 *     ),
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

class UpdateRolesRequest extends FormRequest
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
            'curps' => ['required', 'array'],
            'curps.*' => ['string', 'exists:users,curp'],

            'rolesToAdd' => ['sometimes', 'required', 'array'],
            'rolesToAdd.*' => ['string', 'exists:roles,name'],

            'rolesToRemove' => ['sometimes', 'required', 'array'],
            'rolesToRemove.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'curps.required' => 'Debes proporcionar un array de CURPs.',
            'curps.array' => 'El parámetro curps debe ser un array.',
            'curps.*.exists' => 'Una o más CURPs no existen en el sistema.',

            'rolesToAdd.array' => 'rolesToAdd debe ser un array.',
            'rolesToAdd.*.exists' => 'Uno o más roles a agregar no existen.',

            'rolesToRemove.array' => 'rolesToRemove debe ser un array.',
            'rolesToRemove.*.exists' => 'Uno o más roles a remover no existen.',
        ];
    }
}
