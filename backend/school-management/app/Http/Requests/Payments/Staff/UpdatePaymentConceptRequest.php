<?php

namespace App\Http\Requests\Payments\Staff;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdatePaymentConceptRequest",
 *     type="object",
 *     @OA\Property(
 *         property="concept_name",
 *         type="string",
 *         maxLength=50,
 *         description="Nombre del concepto de pago",
 *         example="Inscripción Semestral"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         maxLength=100,
 *         description="Descripción opcional del concepto",
 *         example="Pago correspondiente al semestre agosto-diciembre"
 *     ),
 *
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de inicio (YYYY-MM-DD)",
 *         example="2025-01-15"
 *     ),
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de fin (opcional, YYYY-MM-DD)",
 *         example="2025-06-30"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         minimum=10,
 *         description="Monto del concepto",
 *         example=1800.50
 *     ),
 *
 *
 * )
 */


class UpdatePaymentConceptRequest extends FormRequest
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
            'concept_name'     => 'sometimes|required|string|max:150',
            'description'      => 'sometimes|required|string|max:255',
            'start_date'       => 'sometimes|required|date_format:Y-m-d',
            'end_date'         => 'sometimes|required|date_format:Y-m-d',
            'amount'           => ['sometimes','required','numeric',
                                    'min:' . config('concepts.amount.min'),
                                    'max:' . config('concepts.amount.max')],


        ];
    }

    public function messages(): array
    {
        return [
            'concept_name.required' => 'El nombre del concepto es obligatorio.',
            'concept_name.max'      => 'El nombre del concepto no puede exceder 50 caracteres.',
            'description.max'       => 'La descripción no puede exceder 100 caracteres.',
            'start_date.date_format'=> 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'end_date.date_format'  => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'amount.numeric'        => 'El monto debe ser numérico.',
        ];
    }

    public function prepareForValidation()
    {

        if ($this->filled('concept_name')) {
            $this->merge([
                'concept_name' => strip_tags($this->concept_name),
            ]);
        }

        if ($this->filled('description')) {
            $this->merge([
                'description' => strip_tags($this->description),
            ]);
        }

    }
}
