<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdatePermissionsToUserRequest",
 *     type="object",
 *
 *     @OA\Property(
 *         property="permissionsToAdd",
 *         type="array",
 *         description="Array de nombres de permisos a agregar",
 *         @OA\Items(
 *             type="string",
 *             description="Nombre de un permiso existente",
 *             example="users.create"
 *         ),
 *         example={"users.create", "reports.view"}
 *     ),
 *     @OA\Property(
 *         property="permissionsToRemove",
 *         type="array",
 *         description="Array de nombres de permisos a remover",
 *         @OA\Items(
 *             type="string",
 *             description="Nombre de un permiso existente",
 *             example="users.delete"
 *         ),
 *         example={"users.delete", "settings.update"}
 *     )
 * )
 */
class UpdatePermissionsToUserRequest extends FormRequest
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
            'permissionsToAdd' => ['sometimes', 'required', 'array'],
            'permissionsToAdd.*' => ['string', 'exists:permissions,name'],

            'permissionsToRemove' => ['sometimes', 'required', 'array'],
            'permissionsToRemove.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissionsToAdd.array' => 'permissionsToAdd debe ser un arreglo.',
            'permissionsToAdd.*.exists' => 'Un permiso a agregar no existe.',

            'permissionsToRemove.array' => 'permissionsToRemove debe ser un arreglo.',
            'permissionsToRemove.*.exists' => 'Un permiso a remover no existe.',
        ];
    }
}
