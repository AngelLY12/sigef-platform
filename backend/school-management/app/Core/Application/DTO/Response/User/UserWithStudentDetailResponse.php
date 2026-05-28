<?php

namespace App\Core\Application\DTO\Response\User;

class UserWithStudentDetailResponse{
 public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $last_name,
        public readonly ?string $email,
        public readonly ?string $phone_number,
        public readonly ?string $birthdate,
        public readonly ?string $gender,
        public readonly ?string $curp,
        public readonly ?array $address,
        public readonly ?string $stripe_customer_id,
        public readonly ?string $blood_type,
        public readonly ?string $registration_date,
        public readonly ?string $status,
        public readonly ?int $career_id = null,
        public readonly ?int $semestre = null,
        public readonly ?string $group = null,
        public readonly ?string $workshop = null,
        public readonly ?string $n_control = null,
    ) {}
}
