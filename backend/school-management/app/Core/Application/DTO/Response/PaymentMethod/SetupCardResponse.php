<?php

namespace App\Core\Application\DTO\Response\PaymentMethod;

/**
 * @OA\Schema(
 *     schema="SetupCardResponse",
 *     type="object",
 *     @OA\Property(property="id", type="string", nullable=true, description="ID de la configuración de la tarjeta", example="pm_01HXXXXXX"),
 *     @OA\Property(property="url", type="string", nullable=true, description="URL para completar el setup de la tarjeta", example="https://example.com/setup/pm_01HXXXXXX")
 * )
 */
class SetupCardResponse
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $url
    )
    {
    }
}
