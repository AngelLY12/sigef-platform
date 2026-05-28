<?php

namespace App\Core\Application\UseCases\Admin\StudentManagement;

use App\Core\Application\DTO\Response\General\ImportResponse;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Jobs\ClearStaffCacheJob;

class BulkImportStudentDetailsUseCase
{
    private const CHUNK_SIZE = 200;
    private const USER_BATCH_SIZE = 1000;
    private const COL_CURP = 0;
    private const COL_CAREER_ID = 1;
    private const COL_N_CONTROL = 2;
    private const COL_SEMESTRE = 3;
    private const COL_GROUP = 4;
    private const COL_WORKSHOP = 5;
    private ImportResponse $importResponse;
    private array $cachedCareerIds =[];

    public function __construct(
        private StudentDetailReInterface $sdRepo,
        private UserQueryRepInterface $userRepo,
        private CareerQueryRepInterface $cqRepo,
    ) {}

    public function execute(array $rows): ImportResponse
    {
        $this->importResponse = new ImportResponse();
        $this->importResponse->setTotalRows(count($rows));
        $hasInsertions = false;
        $this->loadCareerIds();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunkIndex => $chunk) {
            try {
                $chunkResult = $this->processChunk($chunk, $chunkIndex);
                if ($chunkResult->getInserted() > 0) {
                    $hasInsertions = true;
                }
            } catch (\Throwable $e) {
                $this->importResponse->addGlobalError(
                    "Error procesando chunk {$chunkIndex}: " . $e->getMessage(),
                    $chunkIndex,
                    count($chunk)
                );
                logger()->error('Error importing student details: '.$e->getMessage(), [
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => count($chunk),
                    'exception' => $e
                ]);
                continue;
            }
        }
        if($hasInsertions)
        {
            $this->dispatchCacheClear();
        }

        return $this->importResponse;

    }

    private function loadCareerIds(): void
    {
        if (empty($this->cachedCareerIds)) {
            $this->cachedCareerIds = $this->cqRepo->findAllIds();
        }
    }

    private function processChunk(array $rows, int $chunkIndex): ImportResponse
    {
        $curps = $this->extractValidCurps($rows);

        if (empty($curps)) {
            $this->importResponse->addWarning(
                "Chunk {$chunkIndex} sin CURPs válidas",
                $chunkIndex,
                count($rows)
            );
            return $this->importResponse;
        }
        $userMap = $this->buildUserMap($curps);
        $studentDetailsToInsert = $this->prepareStudentDetails($rows, $userMap, $chunkIndex);

        if (empty($studentDetailsToInsert)) {
            $this->importResponse->addWarning(
                "Chunk {$chunkIndex} sin registros válidos para insertar",
                $chunkIndex,
                count($rows)
            );
            return $this->importResponse;
        }

        try {
            $cleanData = $this->cleanForBatchInsert($studentDetailsToInsert);
            $inserted = $this->sdRepo->insertStudentDetails($cleanData);
            $this->importResponse->incrementInserted($inserted);
        } catch (\Exception $e) {
            if ($this->isDuplicateError($e)) {
                $individualResult = $this->insertIndividually($studentDetailsToInsert);
                $this->importResponse->incrementInserted($individualResult['inserted']);

                foreach ($individualResult['errors'] as $error) {
                    $this->importResponse->addError($error['message'], $error['row_number'], $error['context']);
                }
            } else {
                $this->importResponse->addGlobalError(
                    "Error insertando chunk {$chunkIndex}: " . $e->getMessage(),
                    $chunkIndex,
                    count($studentDetailsToInsert)
                );
            }
        }

        return $this->importResponse;
    }

    private function insertIndividually(array $studentDetails): array
    {
        $inserted = 0;
        $errors = [];

        foreach ($studentDetails as $detail) {
            try {
                $rowNumber = $detail['_original_row_number'];
                unset($detail['_original_row_number']);

                $this->sdRepo->insertSingleStudentDetail($detail);
                $inserted++;

            } catch (\Exception $e) {
                if ($this->isDuplicateError($e)) {
                    $errors[] = [
                        'message' => 'Registro duplicado',
                        'row_number' => $rowNumber,
                        'context' => [
                            'user_id' => $detail['user_id'],
                            'n_control' => $detail['n_control']
                        ]
                    ];
                }
            }
        }

        return [
            'inserted' => $inserted,
            'errors' => $errors
        ];
    }

    private function extractValidCurps(array $rows): array
    {
        $curps = [];

        foreach ($rows as $row) {
            if (!empty($row[self::COL_CURP])) {
                $curps[] = $row[self::COL_CURP];
            }
        }

        return array_unique($curps);
    }

    private function cleanForBatchInsert(array $studentDetails): array
    {
        return array_map(function($detail) {
            unset($detail['_original_row_number']);
            return $detail;
        }, $studentDetails);
    }
    private function isDuplicateError(\Exception $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '1062') ||
            str_contains($message, 'Duplicate entry') ||
            str_contains($message, 'Integrity constraint violation') ||
            (str_contains($message, 'SQLSTATE[23000]') && str_contains($message, '1062'));
    }

    private function buildUserMap(array $curps): array
    {
        $userMap = [];

        if (count($curps) > self::USER_BATCH_SIZE) {
            foreach (array_chunk($curps, self::USER_BATCH_SIZE) as $curpChunk) {
                $userMap = array_merge(
                    $userMap,
                    $this->fetchUsersByCurps($curpChunk)
                );
            }
        } else {
            $userMap = $this->fetchUsersByCurps($curps);
        }

        return $userMap;
    }

    private function fetchUsersByCurps(array $curps): array
    {
        $userMap = [];
        $usersGenerator = $this->userRepo->getUsersByCurpCursor($curps);

        foreach ($usersGenerator as $user) {
            $userMap[$user->curp] = $user;
        }

        return $userMap;
    }

    private function prepareStudentDetails(
        array $rows,
        array $userMap,
        int $chunkIndex
    ): array
    {
        $studentDetailsToInsert = [];
        $now = now();
        $invalidCareerCount = 0;
        foreach ($rows as $index => $row) {
            $rowNumber = ($chunkIndex * self::CHUNK_SIZE) + $index + 1;

            if (!$this->isValidRow($row, $userMap, $rowNumber)) {
                continue;
            }

            $user = $userMap[$row[self::COL_CURP]];
            $careerId = (int) $row[self::COL_CAREER_ID];

            $semestre = (int) $row[self::COL_SEMESTRE];
            $maxSemester=config('promotions.max_semester');

            if ($semestre < 1 || $semestre > $maxSemester) {
                $this->importResponse->addError(
                    "Semestre {$semestre} fuera de rango válido (1-12)",
                    $rowNumber,
                    ['curp' => $row[self::COL_CURP], 'semestre' => $semestre]
                );
                continue;
            }

            if (!in_array($careerId, $this->cachedCareerIds, true)) {
                $invalidCareerCount++;
                $this->importResponse->addError(
                    "Carrera con ID {$careerId} no encontrada",
                    $rowNumber,
                    [
                        'curp' => $row[self::COL_CURP],
                        'career_id' => $careerId
                    ]
                );
                continue;
            }

            $studentDetailsToInsert[] = [
                'user_id' => $user->id,
                'career_id' => $careerId,
                'n_control' => trim($row[self::COL_N_CONTROL]),
                'semestre' => $semestre,
                'group' => isset($row[self::COL_GROUP]) ? trim($row[self::COL_GROUP]) : null,
                'workshop' => isset($row[self::COL_WORKSHOP]) ? trim($row[self::COL_WORKSHOP]) : null,
                'created_at' => $now,
                'updated_at' => $now,
                '_original_row_number' => $rowNumber,
            ];

            $this->importResponse->incrementProcessed();
        }
        if ($invalidCareerCount > 0) {
            $this->importResponse->addWarning(
                "{$invalidCareerCount} registro(s) con career_id inválido en el chunk",
                $chunkIndex,
                count($rows)
            );
        }


        return $studentDetailsToInsert;
    }

    private function isValidRow(
        array $row,
        array $userMap,
        int $rowNumber
    ): bool
    {
        $errors = [];

        if (empty($row[self::COL_CURP])) {
            $errors[] = 'CURP requerida';
        } elseif (!isset($userMap[$row[self::COL_CURP]])) {
            $errors[] = 'CURP no encontrada en el sistema';
        }

        if (empty($row[self::COL_CAREER_ID])) {
            $errors[] = 'career_id requerido';
        }elseif (!is_numeric($row[self::COL_CAREER_ID])) { // <-- Nuevo
            $errors[] = 'career_id debe ser numérico';
        }


        if (empty($row[self::COL_N_CONTROL])) {
            $errors[] = 'n_control requerido';
        }

        if (empty($row[self::COL_SEMESTRE])) {
            $errors[] = 'semestre requerido';
        }elseif (!is_numeric($row[self::COL_SEMESTRE])) {
            $errors[] = 'semestre debe ser numérico';
        }

        if (!empty($errors)) {
            $this->importResponse->addError(
                implode(', ', $errors),
                $rowNumber,
                ['curp' => $row[self::COL_CURP] ?? 'N/A']
            );
            return false;
        }

        return true;
    }

    private function dispatchCacheClear(): void
    {
        ClearStaffCacheJob::dispatch()->onQueue('cache');
    }
}
