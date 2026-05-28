<?php

namespace App\Http\Requests\Payments\Staff;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Illuminate\Foundation\Http\FormRequest;


/**
 * @OA\Schema(
 *     schema="ConceptsIndexRequest",
 *     type="object",
 *
 *     @OA\Property(
 *         property="status",
 *         ref="#/components/schemas/PaymentConceptStatus"
 *     ),
 *
 *     @OA\Property(
 *         property="forceRefresh",
 *         type="boolean",
 *         description="Indica si se debe forzar la actualización (opcional)",
 *         example=false
 *     ),
 *
 *     @OA\Property(
 *         property="perPage",
 *         type="integer",
 *         minimum=1,
 *         maximum=200,
 *         description="Número de elementos por página (opcional, entre 1 y 200)",
 *         example=20
 *     ),
 *
 *     @OA\Property(
 *         property="page",
 *         type="integer",
 *         minimum=1,
 *         description="Número de página (opcional, mínimo 1)",
 *         example=1
 *     )
 * )
 */

class ConceptsIndexRequest extends FormRequest
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
            'status'       => ['sometimes', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, PaymentConceptStatus::cases()))],
            'forceRefresh' => ['sometimes', 'boolean'],
            'perPage'      => ['sometimes', 'integer', 'min:1', 'max:200'],
            'page'         => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('forceRefresh')) {
            $this->merge([
                'forceRefresh' => filter_var($this->forceRefresh, FILTER_VALIDATE_BOOLEAN),
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
            'status.in'         => 'El estado proporcionado no es válido.',
            'forceRefresh.boolean' => 'forceRefresh debe ser un valor booleano.',
            'perPage.integer'      => 'perPage debe ser un número entero.',
            'perPage.min'          => 'perPage debe ser al menos 1.',
            'perPage.max'          => 'perPage no puede ser mayor a 200.',
            'page.integer'         => 'page debe ser un número entero.',
            'page.min'             => 'page debe ser al menos 1.',
        ];
    }
}
