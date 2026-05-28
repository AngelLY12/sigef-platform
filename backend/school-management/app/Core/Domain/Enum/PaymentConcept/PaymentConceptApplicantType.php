<?php

namespace App\Core\Domain\Enum\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="PaymentConceptApplicantType",
 *     type="string",
 *     description="Tags de casos especiales",
 *     enum={"applicant", "no_student_details"},
 *     example="applicant"
 * )
 */
enum PaymentConceptApplicantType: string
{
    case APPLICANT = 'applicant';
    case NO_STUDENT_DETAILS = 'no_student_details';

    public static function getTag(string $tag): string
    {
        $value='';
        switch ($tag)
        {
            case 'applicant':
                $value='aplicante';
                break;
            case 'no_student_details':
                $value= 'sin detalles academicos';
                break;
        }
        return $value;
    }
}
