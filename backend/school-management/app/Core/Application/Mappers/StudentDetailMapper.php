<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Models\StudentDetail;

class StudentDetailMapper{

    public static function toCreateStudentDetailDTO(array $data): CreateStudentDetailDTO
    {
        return new CreateStudentDetailDTO(
            user_id: $data['user_id'],
            career_id: $data['career_id'],
            n_control:$data['n_control'],
            semestre: $data['semestre'],
            group: $data['group'],
            workshop:$data['workshop']
        );
    }

    public static function toStudentDetailDTO(StudentDetail $studentDetail): StudentDetailDTO
    {
        if (!$studentDetail->relationLoaded('career')) {
            $studentDetail->load('career:id,career_name');
        }

        return new StudentDetailDTO(
            nControl: $studentDetail->n_control,
            semestre: $studentDetail->semestre,
            group:  $studentDetail->group,
            workshop: $studentDetail->workshop,
            careerName: $studentDetail->career?->career_name,
        );
    }

}
