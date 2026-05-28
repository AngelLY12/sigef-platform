<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="ImportResponse",
 *     type="object",
 *     @OA\Property(
 *         property="summary",
 *         type="object",
 *         @OA\Property(
 *             property="total_rows_received",
 *             type="integer",
 *             example=100,
 *             description="Total de filas recibidas en el archivo"
 *         ),
 *         @OA\Property(
 *             property="rows_processed",
 *             type="integer",
 *             example=95,
 *             description="Filas procesadas después de validación"
 *         ),
 *         @OA\Property(
 *             property="rows_inserted",
 *             type="integer",
 *             example=90,
 *             description="Filas insertadas exitosamente"
 *         ),
 *         @OA\Property(
 *             property="rows_failed",
 *             type="integer",
 *             example=5,
 *             description="Filas que fallaron (no insertadas)"
 *         ),
 *         @OA\Property(
 *             property="success_rate",
 *             type="number",
 *             format="float",
 *             example=94.74,
 *             description="Porcentaje de éxito (rows_inserted/rows_processed)"
 *         )
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *             property="row_errors",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="type", type="string", example="row_error"),
 *                 @OA\Property(property="message", type="string", example="CURP requerida"),
 *                 @OA\Property(property="row_number", type="integer", example=15),
 *                 @OA\Property(
 *                     property="context",
 *                     type="object",
 *                     @OA\Property(property="curp", type="string", example="N/A")
 *                 ),
 *                 @OA\Property(property="timestamp", type="string", format="date-time")
 *             )
 *         ),
 *         @OA\Property(
 *             property="global_errors",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="type", type="string", example="global_error"),
 *                 @OA\Property(property="message", type="string", example="Error en base de datos"),
 *                 @OA\Property(property="chunk_index", type="integer", example=2),
 *                 @OA\Property(property="rows_count", type="integer", example=50),
 *                 @OA\Property(property="timestamp", type="string", format="date-time")
 *             )
 *         ),
 *         @OA\Property(
 *             property="total_errors",
 *             type="integer",
 *             example=3
 *         )
 *     ),
 *     @OA\Property(
 *         property="warnings",
 *         type="object",
 *         @OA\Property(
 *             property="list",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="type", type="string", example="warning"),
 *                 @OA\Property(property="message", type="string", example="Chunk sin datos válidos"),
 *                 @OA\Property(property="chunk_index", type="integer", example=1),
 *                 @OA\Property(property="rows_count", type="integer", example=20),
 *                 @OA\Property(property="timestamp", type="string", format="date-time")
 *             )
 *         ),
 *         @OA\Property(
 *             property="total_warnings",
 *             type="integer",
 *             example=2
 *         )
 *     ),
 *     @OA\Property(
 *         property="timestamp",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-15 10:30:00"
 *     ),
 *     @OA\Property(
 *         property="has_errors",
 *         type="boolean",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="has_warnings",
 *         type="boolean",
 *         example=true
 *     )
 * )
 */
class ImportResponse
{
    public function __construct(
        private int $totalRows = 0,
        private int $rowsProcessed = 0,
        private int $rowsInserted = 0,
        private array $errors = [],
        private array $warnings = [],
        private array $globalErrors = [],
    ){}

    public function incrementInserted(int $count = 1): void
    {
        $this->rowsInserted += $count;
    }

    public function incrementProcessed(int $count = 1): void
    {
        $this->rowsProcessed += $count;
    }

    public function addError(string $message, int $rowNumber, array $context = []): void
    {
        $this->errors[] = [
            'type' => 'row_error',
            'message' => $message,
            'row_number' => $rowNumber,
            'context' => $context,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function addWarning(string $message, int $chunkIndex, int $rowsCount): void
    {
        $this->warnings[] = [
            'type' => 'warning',
            'message' => $message,
            'chunk_index' => $chunkIndex,
            'rows_count' => $rowsCount,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function addGlobalError(string $message, int $chunkIndex, int $rowsCount): void
    {
        $this->globalErrors[] = [
            'type' => 'global_error',
            'message' => $message,
            'chunk_index' => $chunkIndex,
            'rows_count' => $rowsCount,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function merge(ImportResponse $other): void
    {
        $this->rowsInserted += $other->rowsInserted;
        $this->rowsProcessed += $other->rowsProcessed;
        $this->errors = array_merge($this->errors, $other->errors);
        $this->warnings = array_merge($this->warnings, $other->warnings);
        $this->globalErrors = array_merge($this->globalErrors, $other->globalErrors);
    }

    public function toArray(): array
    {
        return [
            'summary' => [
                'total_rows_received' => $this->totalRows,
                'rows_processed' => $this->rowsProcessed,
                'rows_inserted' => $this->rowsInserted,
                'rows_failed' => $this->rowsProcessed - $this->rowsInserted,
                'success_rate' => $this->rowsProcessed > 0
                    ? round(($this->rowsInserted / $this->rowsProcessed) * 100, 2)
                    : 0,
            ],
            'errors' => [
                'row_errors' => $this->errors,
                'global_errors' => $this->globalErrors,
                'total_errors' => count($this->errors) + count($this->globalErrors),
            ],
            'warnings' => [
                'list' => $this->warnings,
                'total_warnings' => count($this->warnings),
            ],
            'timestamp' => now()->toDateTimeString(),
            'has_errors' => !empty($this->errors) || !empty($this->globalErrors),
            'has_warnings' => !empty($this->warnings),
        ];
    }

    public function getSummary(): array
    {
        return $this->toArray()['summary'];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getGlobalErrors(): array
    {
        return $this->globalErrors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function setTotalRows(int $totalRows): void
    {
        $this->totalRows = $totalRows;
    }

    /**
     * @return int
     */
    public function getInserted(): int
    {
        return $this->rowsInserted;
    }

    /**
     * @return int
     */
    public function getProcessed(): int
    {
        return $this->rowsProcessed;
    }

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }
    public function getErrorsCount(): int
    {
        return count($this->errors);
    }

    public function getWarningsCount(): int
    {
        return count($this->warnings);
    }

}
