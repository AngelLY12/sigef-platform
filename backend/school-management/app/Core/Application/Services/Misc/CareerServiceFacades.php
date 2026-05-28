<?php

namespace App\Core\Application\Services\Misc;

use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Career\CreateCareerUseCase;
use App\Core\Application\UseCases\Career\DeleteCareerUseCase;
use App\Core\Application\UseCases\Career\FindAllCareersUseCase;
use App\Core\Application\UseCases\Career\FindCareerUseCase;
use App\Core\Application\UseCases\Career\UpdateCareerUseCase;
use App\Core\Domain\Entities\Career;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Infraestructure\Cache\CacheService;

class CareerServiceFacades
{
     use HasCache;
    private const TAG_CAREERS_ALL = [CachePrefix::CAREERS->value, 'all'];

    public function __construct(
        private CreateCareerUseCase $create,
        private DeleteCareerUseCase $delete,
        private FindAllCareersUseCase $all,
        private FindCareerUseCase $find,
        private UpdateCareerUseCase $update,
        private CacheService $service
    )
    {
        $this->setCacheService($service);

    }

    public function createCareer(Career $career): Career
    {
        return $this->idempotent(
            'career_create',
            [
                'career_id' => $career->id,
                'career_name' => $career->career_name
            ],
            function () use ($career) {
                $career = $this->create->execute($career);
                $this->service->flushTags(self::TAG_CAREERS_ALL);
                return $career;
            }
        );
    }

    public function deleteCareer(int $careerId): void
    {
        $this->idempotent(
            'career.delete',
            ['career_id' => $careerId],
            function () use ($careerId) {
                $this->delete->execute($careerId);
                $this->service->flushTags(self::TAG_CAREERS_ALL);
                return true;
            }
        );
    }

    public function findAllCareers(bool $forceRefresh): array
    {
        $key = $this->generateCacheKey(CachePrefix::CAREERS->value, "all");
        return $this->weeklyCache($key, fn() => $this->all->execute(), self::TAG_CAREERS_ALL ,$forceRefresh,);
    }

    public function findById(int $id, bool $forceRefresh): Career
    {
        return  $this->find->execute($id);
    }

    public function updateCareer(int $careerId, array $fields): Career
    {
        return $this->idempotent(
            'career_update',
            ['career_id' => $careerId, 'fields' => $fields],
            function () use ($careerId, $fields) {
                $career = $this->update->execute($careerId, $fields);
                $this->service->flushTags(self::TAG_CAREERS_ALL);
                return $career;
            }
        );
    }
}
