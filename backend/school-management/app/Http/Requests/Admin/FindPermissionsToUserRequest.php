<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="FindPermissionsToUserRequest",
 *     type="object",
 *
 *     @OA\Property(
 *          property="roles",
 *          type="array",
 *          description="Array de roles del usuario a consultar permisos",
 *          example={"financial-staff", "parent"},
 *          @OA\Items(
 *              type="string",
 *              example="parent",
 *              description="Rol de un usuario existente"
 *          )
 *      ),
 *     @OA\Property(
 *          property="forceRefresh",
 *          type="boolean",
 *          description="Indica si se debe forzar la actualización (opcional)"
 *      ),
 * )
 */
class FindPermissionsToUserRequest extends FormRequest
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
            'roles' => ['required', 'array', 'min:1', 'max:10'],
            'roles.*' => [ 'bail','string', 'exists:roles,name'],
            'forceRefresh' => ['sometimes', 'boolean'],
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->has('roles') && is_string($this->curps)) {
            $roles = array_unique(array_filter(array_map('trim', explode(',', $this->roles))));
            $this->merge([
                'roles' => array_slice($roles, 0, 10)
            ]);
        }
        elseif ($this->has('roles') && is_array($this->roles)) {
            $roles = array_unique(array_filter(array_map('trim', $this->roles)));
            $this->merge([
                'roles' => array_slice($roles, 0, 10)
            ]);
        }

        if ($this->has('roles') && empty($this->curps)) {
            $this->request->remove('roles');
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
            'roles.required' => 'Se requiere al menos un rol.',
            'roles.array' => 'Los roles deben proporcionarse como un array.',
            'roles.min' => 'Debe proporcionar al menos un rol.',
            'roles.max' => 'No se pueden procesar más de 10 roles a la vez.',
            'roles.*.string' => 'Cada rol debe ser una cadena de texto.',
            'roles.*.exists' => 'El rol :input no existe.',
            'forceRefresh.boolean' => 'El valor de forceRefresh debe ser verdadero o falso.',
        ];
    }
}
