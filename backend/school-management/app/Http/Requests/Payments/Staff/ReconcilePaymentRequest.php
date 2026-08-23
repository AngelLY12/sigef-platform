<?php

namespace App\Http\Requests\Payments\Staff;

use Illuminate\Foundation\Http\FormRequest;
/**
 * @OA\Schema(
 *     schema="ReconcilePaymentRequest",
 *     type="object",
 *     required={"user_id", "payment_id"},
 *     @OA\Property(
 *          property="user_id",
 *          type="integer",
 *          description="ID del usuario asociado al pago",
 *          example=123
 *      ),
 *      @OA\Property(
 *          property="payment_id",
 *          type="integer",
 *          description="ID del pago interno que se desea reconciliar",
 *          example=456
 *      )
 * )
 */

class ReconcilePaymentRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.integer' => 'El usuario debe ser un número entero.',
            'user_id.exists' => 'El usuario no existe.',

            'payment_id.required' => 'El pago es obligatorio.',
            'payment_id.integer' => 'El pago debe ser un número entero.',
            'payment_id.exists' => 'El pago no existe.',
        ];
    }
}
