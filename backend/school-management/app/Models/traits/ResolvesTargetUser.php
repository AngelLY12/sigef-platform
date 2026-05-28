<?php

namespace App\Models\traits;

use App\Core\Domain\Enum\User\UserRoles;
use App\Models\User;

trait ResolvesTargetUser
{
    public function resolveTargetUser(?int $id = null): ?User
    {
        if ($this->hasRole(UserRoles::PARENT->value) && $id) {
            $child = $this->children()
                        ->where('student_id', $id)
                        ->with(['student' => function ($query) {
                            $query->with(['roles']);
                        }])
                        ->first();
            if (!$child ||  !$child->student) {
                return null;
            }

            if ($child->student->studentDetail()->exists()) {
                $child->student->load('studentDetail');
            }

            return $child->student;
        }

        $this->loadMissing(['roles']);

        if($this->hasRole(UserRoles::STUDENT->value) && $this->studentDetail()->exists()){
            $this->loadMissing(['studentDetail']);
        }


        return $this;
    }
}
