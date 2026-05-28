<?php

namespace App\Core\Application\DTO\Response\User;

class PromotedStudentsResponse
{
    public function __construct(
        public readonly int $promotedStudents,
        public readonly int $desactivatedStudents,
    )
    {
    }

}
