<?php

namespace Tests\Unit\Domain\Validators;
use App\Core\Domain\Utils\Validators\SemesterPromotionValidator;
use App\Exceptions\Conflict\PromotionAlreadyExecutedException;
use App\Exceptions\NotAllowed\PromotionNotAllowedException;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SemesterPromotionValidatorTest extends TestCase
{
    // Tests para ensurePromotionIsValid
    #[Test]
    public function ensurePromotionIsValid_passes_when_all_conditions_met(): void
    {
        // Configurar un mes permitido (ej. Enero = 1)
        config(['promotions.allowed_months' => [1, 7]]);

        // Mockear now() para que retorne un mes permitido
        Carbon::setTestNow(Carbon::create(2024, 1, 15)); // Enero

        $this->expectNotToPerformAssertions();
        SemesterPromotionValidator::ensurePromotionIsValid(false);
    }

    #[Test]
    public function ensurePromotionIsValid_throws_when_current_month_not_allowed(): void
    {
        // Configurar meses permitidos (ej. solo Enero y Julio)
        config(['promotions.allowed_months' => [1, 7]]);

        // Mockear now() para que retorne un mes NO permitido
        Carbon::setTestNow(Carbon::create(2024, 3, 15)); // Marzo

        $this->expectException(PromotionNotAllowedException::class);
        $this->expectExceptionMessage('La promoción solo se puede ejecutar en los meses permitidos: 1, 7');
        SemesterPromotionValidator::ensurePromotionIsValid(false);
    }

    #[Test]
    public function ensurePromotionIsValid_throws_when_was_already_executed(): void
    {
        // Configurar un mes permitido
        config(['promotions.allowed_months' => [1, 7]]);
        Carbon::setTestNow(Carbon::create(2024, 1, 15)); // Enero

        $this->expectException(PromotionAlreadyExecutedException::class);
        SemesterPromotionValidator::ensurePromotionIsValid(true);
    }

    #[Test]
    public function ensurePromotionIsValid_throws_both_errors_but_month_check_is_first(): void
    {
        // Mes NO permitido Y ya fue ejecutado
        // Debería lanzar primero el error del mes
        config(['promotions.allowed_months' => [1, 7]]);
        Carbon::setTestNow(Carbon::create(2024, 3, 15)); // Marzo

        $this->expectException(PromotionNotAllowedException::class);
        // No debería llegar a verificar si ya fue ejecutado
        SemesterPromotionValidator::ensurePromotionIsValid(true);
    }

    #[Test]
    public function ensurePromotionIsValid_passes_with_different_allowed_months(): void
    {
        // Probar con diferentes configuraciones de meses
        $testCases = [
            [[6, 12], Carbon::create(2024, 6, 1)],  // Junio permitido
            [[6, 12], Carbon::create(2024, 12, 1)], // Diciembre permitido
            [[8], Carbon::create(2024, 8, 15)],     // Solo Agosto permitido
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], Carbon::create(2024, 5, 1)], // Todos los meses
        ];

        foreach ($testCases as [$allowedMonths, $date]) {
            config(['promotions.allowed_months' => $allowedMonths]);
            Carbon::setTestNow($date);

            $this->expectNotToPerformAssertions();
            SemesterPromotionValidator::ensurePromotionIsValid(false);
        }
    }

    #[Test]
    public function ensurePromotionIsValid_throws_with_empty_allowed_months(): void
    {
        // Si no hay meses permitidos, siempre debería fallar
        config(['promotions.allowed_months' => []]);
        Carbon::setTestNow(Carbon::create(2024, 1, 15));

        $this->expectException(PromotionNotAllowedException::class);
        SemesterPromotionValidator::ensurePromotionIsValid(false);
    }

    // Limpiar el mock de Carbon después de cada test
    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Limpiar el mock
        parent::tearDown();
    }
}
