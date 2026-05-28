<?php

namespace App\Core\Domain\Repositories\Command\Misc;

use App\Core\Domain\Entities\Career;

interface CareerRepInterface
{
    public function create(Career $career): Career;
    public function delete(int $careerId): void;
    public function update(int $careerId, array $fields): Career;

}
