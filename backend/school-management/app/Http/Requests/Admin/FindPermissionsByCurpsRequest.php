<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="FindPermissionsByCurpsRequest",
 *     type="object",
 *     @OA\Property(
 *         property="curps",
 *         type="array",
 *         description="Array de CURPs de los usuarios a consultar permisos",
 *         example={"LOPA800101HDFRNL09", "MARA900202MDFRTN05"},
 *         @OA\Items(
 *             type="string",
 *             example="LOPA800101HDFRNL09",
 *             description="CURP de un usuario existente"
 *         )
 *     ),
 *
 * )
 */

class FindPermissionsByCurpsRequest extends FormRequest
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
            'curps' => ['required', 'array', 'min:1', 'max:100'],
            'curps.*' => [ 'bail','string', 'size:18', 'exists:users,curp'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('curps') && is_string($this->curps)) {
            $curps = array_unique(array_filter(array_map('trim', explode(',', $this->curps))));
            $this->merge([
                'curps' => array_slice($curps, 0, 100)
            ]);
        }
        elseif ($this->has('curps') && is_array($this->curps)) {
            $curps = array_unique(array_filter(array_map('trim', $this->curps)));
            $this->merge([
                'curps' => array_slice($curps, 0, 100)
            ]);
        }

        if ($this->has('curps') && empty($this->curps)) {
            $this->request->remove('curps');
        }
    }

    public function messages(): array
    {
        return [
            'curps.required' => 'Se requiere al menos un CURP.',
            'curps.array' => 'Los CURPs deben proporcionarse como un array.',
            'curps.min' => 'Debe proporcionar al menos un CURP.',
            'curps.max' => 'No se pueden procesar más de 50 CURPs a la vez.',
            'curps.*.string' => 'Cada CURP debe ser una cadena de texto.',
            'curps.*.size' => 'El CURP :input debe tener exactamente 18 caracteres.',
            'curps.*.exists' => 'El CURP :input no existe o el usuario ha sido eliminado.',
        ];
    }
}
