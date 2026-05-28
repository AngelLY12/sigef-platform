<?php

namespace App\Http\Requests\Payments\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ValidatePaymentRequest",
 *     type="object",
 *     @OA\Property(
 *         property="search",
 *         type="string",
 *         description="Término de búsqueda para validar el pago",
 *         example="user@example.com"
 *     ),
 *     @OA\Property(
 *         property="payment_intent_id",
 *         type="string",
 *         description="ID del intento de pago a validar",
 *         example="pi_1Hh1XYZ1234567890"
 *     )
 * )
 */

class ValidatePaymentRequest extends FormRequest
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
            'search'=> ['required', 'string'],
            'payment_intent_id' => ['required', 'string'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->filled('search')) {
            $this->merge([
                'search' => strip_tags($this->search),
                'payment_intent_id' => strip_tags($this->payment_intent_id),

            ]);
        }
    }

    public function messages(): array
    {
        return [
            'search.required'            => 'El campo search es obligatorio.',
            'search.string'              => 'El campo search debe ser una cadena de texto.',
            'payment_intent_id.required' => 'El campo payment_intent_id es obligatorio.',
            'payment_intent_id.string'   => 'El campo payment_intent_id debe ser una cadena de texto.',
        ];
    }
}
