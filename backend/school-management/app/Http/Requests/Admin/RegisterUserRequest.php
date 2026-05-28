<?php

namespace App\Http\Requests\Admin;

use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use Illuminate\Foundation\Http\FormRequest;


/**
 * @OA\Schema(
 *     schema="RegisterUserRequest",
 *     type="object",
 *     required={"name","last_name","email","phone_number","curp","status"},
 *
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nombre del usuario",
 *         example="Juan"
 *     ),
 *     @OA\Property(
 *         property="last_name",
 *         type="string",
 *         description="Apellido del usuario",
 *         example="Pérez López"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="Correo electrónico del usuario",
 *         example="juan.perez@example.com"
 *     ),
 *     @OA\Property(
 *         property="phone_number",
 *         type="string",
 *         description="Número de teléfono del usuario",
 *         example="5512345678"
 *     ),
 *     @OA\Property(
 *         property="birthdate",
 *         type="string",
 *         format="date",
 *         description="Fecha de nacimiento del usuario (YYYY-MM-DD)",
 *         example="2000-05-12"
 *     ),
 *     @OA\Property(
 *         property="gender",
 *          ref="#/components/schemas/UserGender"
 *     ),
 *     @OA\Property(
 *         property="curp",
 *         type="string",
 *         description="CURP del usuario",
 *         example="LOPA800101HDFRNL09"
 *     ),
 *     @OA\Property(
 *         property="address",
 *         type="array",
 *         description="Dirección del usuario",
 *         example={"Calle Hidalgo #123", "Col. Centro", "CDMX"},
 *         @OA\Items(type="string")
 *     ),
 *     @OA\Property(
 *         property="blood_type",
 *         ref="#/components/schemas/UserBloodType"
 *     ),
 *     @OA\Property(
 *         property="registration_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de registro del usuario (YYYY-MM-DD)",
 *         example="2025-01-01"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         ref="#/components/schemas/UserStatus"
 *     )
 * )
 */

class RegisterUserRequest extends FormRequest
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
            'name' => 'required|string',
            'last_name'  => 'required|string',
            'email'  => 'required|email:rfc,dns|unique:users,email',
            'phone_number'  => 'required|string|regex:/^\+52\d{10}$/',
            'birthdate' => 'sometimes|required|date|date_format:Y-m-d',
            'gender'       => [
                'sometimes',
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, UserGender::cases())),
            ],
            'curp' => 'required|string|size:18|alpha_num|uppercase|unique:users,curp',
            'address' => 'sometimes|required|array',
            'blood_type'   => [
                'sometimes',
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, UserBloodType::cases())),
            ],
            'registration_date' => 'sometimes|required|date|date_format:Y-m-d',
            'status' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, UserStatus::cases()))
            ],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'name' => $this->has('name') ? strip_tags($this->name) : null,
            'last_name' => $this->has('last_name') ? strip_tags($this->last_name) : null,
            'phone_number' => $this->has('phone_number') ? strip_tags($this->phone_number) : null,
            'curp' => $this->has('curp') ? strip_tags($this->curp) : null,
        ]);

        if ($this->has('gender')) {
            $this->merge([
                'gender' => strtolower($this->gender),
            ]);
        }

        if($this->has('blood_type'))
        {
            $this->merge([
                'blood_type' => strtoupper($this->blood_type)
            ]);
        }

        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->status),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'phone_number.required' => 'El número de teléfono es obligatorio.',
            'phone_number.string' => 'El número de teléfono debe ser texto.',
            'phone_number.regex' => 'El teléfono debe ser un número mexicano válido: +521234567890',

            'birthdate.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'birthdate.date_format' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
            'birthdate.required' => 'La fecha de nacimiento es obligatoria.',

            'gender.required' => 'El género es obligatorio.',
            'gender.in'          => 'El género no es válido. Debe ser: ' . implode(',', array_map(fn($case) => $case->value, UserGender::cases())),
            'gender.string' => 'El género debe ser texto.',

            'curp.required' => 'La CURP es obligatoria.',
            'curp.string' => 'La CURP debe ser texto.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.alpha_num' => 'La CURP debe contener solo letras y números.',
            'curp.uppercase' => 'La CURP debe contener solo letras mayusculas.',
            'curp.unique' => 'Esta CURP ya está registrada.',

            'address.array' => 'La dirección debe ser un arreglo válido.',
            'address.required' => 'La dirección es obligatoria.',

            'blood_type.required' => 'El tipo de sangre es obligatorio.',
            'blood_type.in'      => 'El tipo de sangre no es válido. Debe ser: ' . implode(',', array_map(fn($case) => $case->value, UserBloodType::cases())),
            'blood_type.string' => 'El tipo de sangre debe ser texto.',

            'registration_date.date' => 'La fecha de registro debe ser una fecha válida.',
            'registration_date.date_format' => 'La fecha de registro debe tener el formato AAAA-MM-DD.',
            'registration_date.required' => 'La fecha de registro es obligatoria.',

            'status.required' => 'El estatus es obligatorio.',
            'status.in' => 'El estatus proporcionado no es válido. Debe ser: ' . implode(',', array_map(fn($case) => $case->value, UserStatus::cases())),
            'status.string' => 'El estatus debe ser texto.',

            'name.string' => 'El nombre debe ser una cadena de texto.',
            'last_name.string' => 'El apellido debe ser una cadena de texto.',
        ];
    }
}
