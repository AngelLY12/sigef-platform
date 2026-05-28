<?php

namespace App\Http\Requests\Career;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateCareerRequest",
 *     type="object",
 *     required={"career_name"},
 *     @OA\Property(
 *         property="career_name",
 *         type="string",
 *         maxLength=50,
 *         description="Nombre de la carrera",
 *         example="Contabilidad"
 *     )
 * )
 */
class UpdateCareerRequest extends FormRequest
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
            'career_name' => 'sometimes|string|max:150',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'career_name' => $this->has('career_name') ? strip_tags($this->career_name) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'career_name.required' => 'El nombre de la carrera es obligatorio.',
            'career_name.string' => 'El nombre de la carrera debe ser un texto.',
            'career_name.max' => 'El nombre de la carrera no puede exceder 50 caracteres.',
        ];
    }
}
