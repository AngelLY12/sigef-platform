<?php

namespace App\Http\Requests\General;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="PaginationRequest",
 *     type="object",
 *     @OA\Property(
 *         property="forceRefresh",
 *         type="boolean",
 *         description="Indica si se debe forzar la actualización (opcional)"
 *     ),
 *     @OA\Property(
 *          property="only_this_year",
 *          type="boolean",
 *          description="Indica si se debe mostrar datos de este año o de todos (opcional, aplica solo en Dashboard)"
 *      ),
 *     @OA\Property(
 *         property="perPage",
 *         type="integer",
 *         minimum=1,
 *         maximum=200,
 *         description="Número de elementos por página (opcional, entre 1 y 200)",
 *         example=20
 *     ),
 *     @OA\Property(
 *         property="page",
 *         type="integer",
 *         minimum=1,
 *         description="Número de página (opcional, mínimo 1)",
 *         example=1
 *     )
 * )
 */

class PaginationRequest extends FormRequest
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
            'forceRefresh' => ['sometimes', 'boolean'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'only_this_year' => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('forceRefresh')) {
            $this->merge([
                'forceRefresh' => filter_var($this->forceRefresh, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        if ($this->has('only_this_year')) {
            $this->merge([
                'only_this_year' => filter_var($this->only_this_year, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'forceRefresh.boolean' => 'El valor de forceRefresh debe ser verdadero o falso.',
            'only_this_year.boolean' => 'El valor de only_this_year debe ser verdadero o falso.',
            'perPage.integer' => 'perPage debe ser un número entero.',
            'perPage.min' => 'perPage debe ser al menos 1.',
            'perPage.max' => 'perPage no puede ser mayor a 200.',
            'page.integer' => 'page debe ser un número entero.',
            'page.min' => 'page debe ser al menos 1.',
        ];
    }
}
