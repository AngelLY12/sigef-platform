<?php

namespace App\Core\Application\DTO\Response\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentReconciliationResponse",
 *     type="object",
 *     @OA\Property(
 *         property="paymentId",
 *         type="integer",
 *         example=123
 *     ),
 *     @OA\Property(
 *         property="reconciled",
 *         type="boolean",
 *         example=true,
 *         description="Indica si la reconciliación pudo completarse."
 *     ),
 *     @OA\Property(
 *         property="source",
 *         type="string",
 *         nullable=true,
 *         example="cargo",
 *         description="Fuente mediante la cual se realizó o confirmó la reconciliación."
 *     ),
 *     @OA\Property(
 *         property="changes",
 *         type="object",
 *         description="Indica los datos del pago que fueron modificados durante la reconciliación.",
 *         @OA\AdditionalProperties(
 *             type="boolean",
 *             example=true
 *         )
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         nullable=true,
 *         example="El pago fue reconciliado correctamente.",
 *         description="Mensaje descriptivo del resultado de la reconciliación."
 *     )
 * )
 */
class PaymentReconciliationResponse
{
    public function __construct(
        public int $paymentId,
        public bool $reconciled,
        public ?string $source,
        public array $changes = [],
        public ?string $message = null,
    ) {}

    public static function create(int $paymentId): self
    {
        return new self(
            paymentId: $paymentId,
            reconciled: false,
            source: null,
        );
    }

    public function processReconciliation(string $source, array $changes, string $message): void
    {
        $this->reconciled = true;
        $this->source = $source;
        $this->changes = $changes;
        $this->message = $message;
    }

    public function failedReconciliation(string $errorMessage): void
    {
        $this->reconciled = false;
        $this->source = null;
        $this->changes = [];
        $this->message = $errorMessage;
    }


}
