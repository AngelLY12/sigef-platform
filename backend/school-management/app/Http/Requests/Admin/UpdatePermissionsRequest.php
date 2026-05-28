<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdatePermissionsRequest",
 *     type="object",
 *     @OA\Property(
 *         property="curps",
 *         type="array",
 *         description="Array de CURPs de los usuarios a actualizar permisos (opcional, no enviar si se usa role)",
 *         @OA\Items(
 *             type="string",
 *             description="CURP de un usuario existente",
 *             example="GODE561231HDFABC09"
 *         ),
 *         example={"GODE561231HDFABC09", "PEMJ800101MDFLRS08"}
 *     ),
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *         description="Nombre del rol cuyos permisos se actualizarán (opcional, no enviar si se usan curps)",
 *         example="admin"
 *     ),
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

class UpdatePermissionsRequest extends FormRequest
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
            'curps' => ['sometimes', 'required_without:role', 'array'],
            'curps.*' => ['string', 'exists:users,curp'],

            'role' => ['sometimes', 'required_without:curps', 'string', 'exists:roles,name'],

            'permissionsToAdd' => ['sometimes', 'array'],
            'permissionsToAdd.*' => ['string', 'exists:permissions,name'],

            'permissionsToRemove' => ['sometimes', 'array'],
            'permissionsToRemove.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasCurps = $this->filled('curps');
            $hasRole = $this->filled('role');

            if (!$hasCurps && !$hasRole) {
                $validator->errors()->add('curps', 'Debes proporcionar CURPs o rol.');
                return;
            }

            if ($hasCurps && $hasRole) {
                $validator->errors()->add('curps', 'No puedes especificar CURPs y rol al mismo tiempo.');
            }

            $hasPermissionsToAdd = $this->filled('permissionsToAdd');
            $hasPermissionsToRemove = $this->filled('permissionsToRemove');

            if (!$hasPermissionsToAdd && !$hasPermissionsToRemove) {
                $validator->errors()->add('permissionsToAdd', 'Debes proporcionar permisos para agregar o remover.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'curps.array' => 'El campo curps debe ser un arreglo.',
            'curps.*.exists' => 'Una o más CURPs no existen en el sistema.',

            'role.exists' => 'El rol especificado no existe.',

            'permissionsToAdd.array' => 'permissionsToAdd debe ser un arreglo.',
            'permissionsToAdd.*.exists' => 'Un permiso a agregar no existe.',

            'permissionsToRemove.array' => 'permissionsToRemove debe ser un arreglo.',
            'permissionsToRemove.*.exists' => 'Un permiso a remover no existe.',
        ];
    }
}
