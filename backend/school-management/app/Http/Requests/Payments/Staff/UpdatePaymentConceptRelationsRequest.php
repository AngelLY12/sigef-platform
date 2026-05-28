<?php

namespace App\Http\Requests\Payments\Staff;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="UpdatePaymentConceptRelationsRequest",
 *     type="object",
 *
 *     @OA\Property(
 *         property="applies_to",
 *         ref="#/components/schemas/PaymentConceptAppliesTo",
 *     ),
 *     @OA\Property(
 *         property="semestres",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         description="Array de semestres a los que aplica (opcional)",
 *         example={1,2,3}
 *     ),
 *     @OA\Property(
 *         property="careers",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         description="Array de IDs de carreras a los que aplica (opcional)",
 *         example={3,5}
 *     ),
 *     @OA\Property(
 *         property="students",
 *         type="array",
 *         @OA\Items(type="string"),
 *         description="Array de numeros de control de estudiantes a los que aplica (opcional)",
 *         example={"21","22","23"}
 *     ),
 *     @OA\Property(
 *         property="replaceRelations",
 *         type="boolean",
 *         description="Indica si se deben reemplazar las relaciones existentes",
 *         example=true
 *     ),
 *      @OA\Property(
 *         property="exceptionStudents",
 *         type="array",
 *         description="Array de numeros de control de estudiantes a los que no aplica el concepto por alguna razón(opcional)",
 *         @OA\Items(type="string"),
 *         example={"11","60","90"}
 *     ),
 *     @OA\Property(
 *         property="replaceExceptions",
 *         type="boolean",
 *         description="Indica si se deben reemplazar las relaciones de exceptions",
 *         example=true
 *     ),
 *     @OA\Property(
 *          property="removeAllExceptions",
 *          type="boolean",
 *          description="Indica si se deben eliminar todas las exceptions",
 *          example=true
 *      ),
 *     @OA\Property(
 *          property="applicantTags",
 *          type="array",
 *          description="Array de casos especiales para aplicar un concepto (opcional)",
 *          @OA\Items(type="string"),
 *          example={"applicants"}
 *      ),
 * )
 */
class UpdatePaymentConceptRelationsRequest extends FormRequest
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
            'applies_to'       => ['sometimes', Rule::enum(PaymentConceptAppliesTo::class)],
            'careers'        => [
                'array',
                'required_if:applies_to,carrera,carrera_semestre',
                'prohibited_unless:applies_to,carrera,carrera_semestre'
            ],
            'careers.*'      => ['integer'],
            'semestres'      => [
                'array',
                'required_if:applies_to,semestre,carrera_semestre',
                'prohibited_unless:applies_to,semestre,carrera_semestre',
            ],
            'semestres.*'    => ['integer'],

            'students'       => [
                'array',
                'required_if:applies_to,estudiantes',
                'prohibited_unless:applies_to,estudiantes',
            ],
            'students.*'     => ['string'],
            'exceptionStudents'    => ['sometimes','array'],
            'exceptionStudents.*'  => ['string'],
            'applicantTags' => [
                'array',
                'required_if:applies_to,tag',
                'prohibited_unless:applies_to,tag',
            ],
            'applicantTags.*' => Rule::enum(PaymentConceptApplicantType::class),
            'replaceRelations' => ['sometimes', 'boolean'],
            'replaceExceptions' => ['sometimes', 'boolean'],
            'removeAllExceptions' => ['sometimes', 'boolean'],

        ];
    }

    public function prepareForValidation()
    {

        if ($this->has('applies_to')) {
            $this->merge([
                'applies_to' => strtolower($this->applies_to),
            ]);
        }

        foreach ([
                     'replaceRelations',
                     'replaceExceptions',
                     'removeAllExceptions',
                 ] as $flag) {
            if ($this->has($flag)) {
                $this->merge([
                    $flag => filter_var($this->$flag, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
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
                $this->hasAny(['careers', 'semestres', 'students', 'applicantTags']) &&
                ! $this->has('applies_to')
            ) {
                $validator->errors()->add(
                    'applies_to',
                    'applies_to es obligatorio cuando se envían relaciones.'
                );
            }

            if (
                $this->applies_to === PaymentConceptAppliesTo::TODOS->value &&
                $this->hasAny(['careers', 'semestres', 'students', 'applicantTags'])
            ) {
                $validator->errors()->add(
                    'applies_to',
                    'No se permiten relaciones cuando applies_to es TODOS.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'applies_to.in'         => 'El valor de applies_to no es válido.',
            'semestres.array'        => 'Semestres debe ser un arreglo.',
            'semestres.*.integer'    => 'Cada semestre debe ser un número entero.',
            'careers.array'          => 'Careers debe ser un arreglo.',
            'careers.*.integer'      => 'Cada career debe ser un número entero.',
            'students.array'         => 'Students debe ser un arreglo.',
            'students.*.string'      => 'Cada student debe ser una cadena válida.',
            'exceptionStudents.array'     => 'exceptionStudents debe ser un arreglo.',
            'exceptionStudents.*.string'  => 'Cada exceptionStudent debe ser una cadena válida.',
            'replaceRelations.boolean' => 'replaceRelations debe ser booleano.',
            'replaceExceptions.boolean' => 'replaceExceptions debe ser booleano.',
            'removeAllExceptions.boolean' => 'removeAllExceptions debe ser booleano.',
            'applicantTags.array' => 'ApplicantTags debe ser un arreglo.',
            'applicantTags.*.in' => 'Cada applicantTag debe ser uno de los valores permitidos: ' . implode(', ', array_map(fn($case) => $case->value, PaymentConceptApplicantType::cases())),
        ];
    }
}
