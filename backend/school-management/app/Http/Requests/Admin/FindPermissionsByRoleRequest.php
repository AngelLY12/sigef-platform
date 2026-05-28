<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="FindPermissionsByRoleRequest",
 *     type="object",
 *
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *        description="Nombre del rol para consultar permisos",
 *        example="student"
 *     ),
 *     @OA\Property(
 *          property="forceRefresh",
 *          type="boolean",
 *          description="Indica si se debe forzar la actualización (opcional)"
 *      ),
 * )
 */
class FindPermissionsByRoleRequest extends FormRequest
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
            'role' => ['required', 'string', 'exists:roles,name'],
            'forceRefresh' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('role')) {
            $role = strtolower(trim($this->input('role')));
            $this->merge(['role' => $role]);
        }
        if ($this->has('forceRefresh')) {
            $this->merge([
                'forceRefresh' => filter_var($this->forceRefresh, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'forceRefresh.boolean' => 'El valor de forceRefresh debe ser verdadero o falso.',
            'role.required' => 'El campo rol es requerido.',
            'role.string' => 'El rol debe ser una cadena de texto.',
            'role.exists' => 'El rol proporcionado no existe.',
        ];
    }
}
