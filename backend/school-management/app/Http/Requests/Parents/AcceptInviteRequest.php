<?php

namespace App\Http\Requests\Parents;

use App\Core\Domain\Enum\User\RelationshipType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AcceptInviteRequest",
 *     type="object",
 *     required={"token"},
 *     @OA\Property(
 *         property="token",
 *         type="string",
 *         description="Token de la invitación",
 *         example="abc123xyzInviteToken987"
 *     ),
 *     @OA\Property(
 *         property="relationship",
 *         ref="#/components/schemas/RelationshipType"
 *     )
 * )
 */

class AcceptInviteRequest extends FormRequest
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
            'token'        => 'required|string',
            'relationship' => [
            'nullable',
            'string',
            'max:50',
            'in:' . implode(',', array_map(fn($case) => $case->value, RelationshipType::cases())),
        ],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('relationship')) {
            $this->merge([
                'relationship' => strtolower($this->relationship),
            ]);
        }
    }
    public function messages(): array
    {
        return [
            'token.required' => 'El token de la invitación es obligatorio.',
            'token.string'   => 'El token debe ser un valor de texto válido.',
            'relationship.string'  => 'La relación debe ser un texto válido.',
            'relationship.max'     => 'La relación no puede exceder 50 caracteres.',
            'relationship.in'      => 'La relación debe ser una de las siguientes: ' . implode(', ', array_map(fn($case) => $case->value, RelationshipType::cases())),
        ];
    }
}
