<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Domain\Entities\StudentDetail as DomainStudentDetail;
use App\Models\StudentDetail;

class StudentDetailMapper{

    public static function toDomain(?StudentDetail $studentDetail): ?DomainStudentDetail
    {
        if(!$studentDetail)
        {
            return null;
        }
        return new DomainStudentDetail(
            user_id: $studentDetail->user_id,
            id: $studentDetail->id,
            career_id: $studentDetail->career_id ?? null,
            n_control: $studentDetail->n_control ?? null,
            semestre: $studentDetail->semestre ?? null,
            group: $studentDetail->group ?? null,
            workshop: $studentDetail->workshop ?? null
        );

    }

    public static function toPersistence(CreateStudentDetailDTO $studentDetail): array
    {
        return [
            'user_id' => $studentDetail->user_id,
            'career_id' => $studentDetail->career_id ?? null,
            'n_control' => $studentDetail->n_control ?? null,
            'semestre' => $studentDetail->semestre ?? null,
            'group' => $studentDetail->group ?? null,
            'workshop' => $studentDetail->workshop ?? null
        ];
    }
}
