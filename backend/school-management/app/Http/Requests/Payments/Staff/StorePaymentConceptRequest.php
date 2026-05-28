<?php

namespace App\Http\Requests\Payments\Staff;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Models\PaymentConceptApplicantTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StorePaymentConceptRequest",
 *     type="object",
 *     required={"concept_name","start_date","amount","applies_to"},
 *
 *     @OA\Property(
 *         property="concept_name",
 *         type="string",
 *         maxLength=50,
 *         description="Nombre del concepto de pago",
 *         example="Inscripción 2025"
 *     ),
 *
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         maxLength=100,
 *         description="Descripción opcional del concepto",
 *         example="Pago correspondiente al proceso de inscripción del ciclo 2025"
 *     ),
 *
 *     @OA\Property(
 *         property="status",
 *         ref="#/components/schemas/PaymentConceptStatus",
 *     ),
 *
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de inicio (YYYY-MM-DD)",
 *         example="2025-01-15"
 *     ),
 *
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de fin (opcional, YYYY-MM-DD)",
 *         example="2025-03-01"
 *     ),
 *
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         minimum=10,
 *         description="Monto del concepto",
 *         example=1500.50
 *     ),
 *
 *     @OA\Property(
 *         property="applies_to",
 *         ref="#/components/schemas/PaymentConceptAppliesTo",
 *     ),
 *
 *     @OA\Property(
 *         property="semestres",
 *         type="array",
 *         description="Array de semestres a los que aplica (opcional)",
 *         @OA\Items(type="integer"),
 *         example={1,2,3}
 *     ),
 *
 *     @OA\Property(
 *         property="careers",
 *         type="array",
 *         description="Array de IDs de carreras a los que aplica (opcional)",
 *         @OA\Items(type="integer"),
 *         example={4,7}
 *     ),
 *
 *     @OA\Property(
 *         property="students",
 *         type="array",
 *         description="Array de numeros de control de estudiantes a los que aplica (opcional)",
 *         @OA\Items(type="string"),
 *         example={"12","55","89"}
 *     ),
 *      @OA\Property(
 *         property="exceptionStudents",
 *         type="array",
 *         description="Array de numeros de control de estudiantes a los que no aplica el concepto por alguna razón(opcional)",
 *         @OA\Items(type="string"),
 *         example={"11","60","90"}
 *     ),
 *     @OA\Property(
 *           property="applicantTags",
 *           type="array",
 *           description="Array de casos especiales para aplicar un concepto (opcional)",
 *           @OA\Items(type="string"),
 *           example={"applicants"}
 *      ),
 * )
 */


class StorePaymentConceptRequest extends FormRequest
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
            'concept_name' => 'required|string|max:150',
            'description'  => 'nullable|string|max:255',
            'status'       => ['sometimes', Rule::enum(PaymentConceptStatus::class)],
            'start_date'   => 'required|date_format:Y-m-d',
            'end_date'     => 'nullable|date_format:Y-m-d',
            'amount'       => ['required','numeric',
                                'min:' . config('concepts.amount.min'),
                                'max:' . config('concepts.amount.max')],
            'applies_to'   => ['required', Rule::enum(PaymentConceptAppliesTo::class)],
            'careers' => [
                'array',
                'required_if:applies_to,carrera,carrera_semestre',
                'prohibited_unless:applies_to,carrera,carrera_semestre',
            ],
            'careers.*' => ['integer'],
            'semestres'      => [
                'array',
                'required_if:applies_to,semestre,carrera_semestre',
                'prohibited_unless:applies_to,semestre,carrera_semestre',
            ],
            'semestres.*' => ['integer'],
            'students'       =>[
                'array',
                'required_if:applies_to,estudiantes',
                'prohibited_unless:applies_to,estudiantes',
            ],
            'students.*' => ['string'],
            'exceptionStudents'    => ['sometimes','array'],
            'exceptionStudents.*' => ['string'],
            'applicantTags' => [
                'array',
                'required_if:applies_to,tag',
                'prohibited_unless:applies_to,tag',
            ],
            'applicantTags.*' => Rule::enum(PaymentConceptApplicantType::class),
        ];
    }

    public function prepareForValidation()
    {
        if($this->has('concept_name'))
        {
            $this->merge([
                'concept_name' => strip_tags($this->concept_name),
            ]);
        }
        if ($this->has('applies_to')) {
            $this->merge([
                'applies_to' => strtolower($this->applies_to),
            ]);
        }
        if ($this->filled('description')) {
            $this->merge([
                'description' => strip_tags($this->description),
            ]);
        }

        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->status),
            ]);
        }
        foreach ([
                     'careers',
                     'semestres',
                     'students',
                     'exceptionStudents',
                     'applicantTags',
                 ] as $field) {
            if ($this->has($field) && is_array($this->input($field))) {
                $this->merge([
                    $field => array_values($this->input($field)),
                ]);
            }
        }

        if ($this->has('applicantTags')) {
            $this->merge([
                'applicantTags' => array_map('strtolower', $this->applicantTags),
            ]);
        }

    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (
                $this->applies_to === PaymentConceptAppliesTo::TODOS->value &&
                $this->hasAny(['careers', 'semestres', 'students', 'applicantTags'])
            ) {
                $validator->errors()->add(
                    'applies_to',
                    'No se permiten filtros cuando applies_to es TODOS'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'concept_name.required' => 'El nombre del concepto es obligatorio.',
            'concept_name.max' => 'El nombre del concepto no puede exceder 150 caracteres.',

            'description.max' => 'La descripción no puede exceder 255 caracteres.',

            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',

            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser numérico.',

            'applies_to.required' => 'El campo applies_to es obligatorio.',
            'applies_to.enum' => 'El valor de applies_to no es válido.',

            'careers.array' => 'Careers debe ser un arreglo.',
            'careers.*.integer' => 'Cada career debe ser un número entero.',

            'semestres.array' => 'Semestres debe ser un arreglo.',
            'semestres.*.integer' => 'Cada semestre debe ser un número entero.',

            'students.array' => 'Students debe ser un arreglo.',
            'students.*.string' => 'Cada student debe ser una cadena válida.',

            'exceptionStudents.array' => 'exceptionStudents debe ser un arreglo.',
            'exceptionStudents.*.string' => 'Cada exceptionStudent debe ser una cadena válida.',

            'applicantTags.array' => 'ApplicantTags debe ser un arreglo.',
            'applicantTags.*.enum' =>
                'Cada applicantTag debe ser uno de los valores permitidos.',

        ];
    }
}
