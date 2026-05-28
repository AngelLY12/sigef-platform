<?php

namespace Tests\Unit\Domain\Enum;

use App\Core\Domain\Enum\User\UserStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Domain\EnumTestCase;

class UserStatusTest extends EnumTestCase
{
    protected function enumClass(): string
    {
        return UserStatus::class;
    }

    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = $this->getAllCases();

        $this->assertCount(4, $cases, 'UserStatus debe tener 4 casos');

        $expectedValues = [
            'activo',
            'baja-temporal',
            'baja',
            'eliminado',
        ];

        foreach ($expectedValues as $expectedValue) {
            $this->assertContains(
                $expectedValue,
                $this->getAllValues(),
                "UserStatus debe incluir '{$expectedValue}'"
            );
        }
    }

    #[Test]
    public function it_has_correct_allowed_transitions(): void
    {
        $activoTransitions = UserStatus::ACTIVO->allowedTransitions();
        $this->assertCount(3, $activoTransitions);
        $this->assertContains(UserStatus::BAJA, $activoTransitions);
        $this->assertContains(UserStatus::BAJA_TEMPORAL, $activoTransitions);
        $this->assertContains(UserStatus::ELIMINADO, $activoTransitions);
        $this->assertNotContains(UserStatus::ACTIVO, $activoTransitions);

        $bajaTransitions = UserStatus::BAJA->allowedTransitions();
        $this->assertCount(2, $bajaTransitions);
        $this->assertContains(UserStatus::ACTIVO, $bajaTransitions);
        $this->assertContains(UserStatus::ELIMINADO, $bajaTransitions);
        $this->assertNotContains(UserStatus::BAJA, $bajaTransitions);
        $this->assertNotContains(UserStatus::BAJA_TEMPORAL, $bajaTransitions);

        $bajaTemporalTransitions = UserStatus::BAJA_TEMPORAL->allowedTransitions();
        $this->assertCount(3, $bajaTemporalTransitions);
        $this->assertContains(UserStatus::ACTIVO, $bajaTemporalTransitions);
        $this->assertContains(UserStatus::BAJA, $bajaTemporalTransitions);
        $this->assertContains(UserStatus::ELIMINADO, $bajaTemporalTransitions);
        $this->assertNotContains(UserStatus::BAJA_TEMPORAL, $bajaTemporalTransitions);

        $eliminadoTransitions = UserStatus::ELIMINADO->allowedTransitions();
        $this->assertCount(1, $eliminadoTransitions);
        $this->assertContains(UserStatus::ACTIVO, $eliminadoTransitions);
        $this->assertNotContains(UserStatus::BAJA, $eliminadoTransitions);
        $this->assertNotContains(UserStatus::BAJA_TEMPORAL, $eliminadoTransitions);
        $this->assertNotContains(UserStatus::ELIMINADO, $eliminadoTransitions);
    }

    #[Test]
    public function it_validates_transitions_correctly(): void
    {
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA));
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA_TEMPORAL));
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::ELIMINADO));
        $this->assertFalse(UserStatus::ACTIVO->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ACTIVO));
        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ELIMINADO));
        $this->assertFalse(UserStatus::BAJA->canTransitionTo(UserStatus::BAJA_TEMPORAL));
        $this->assertFalse(UserStatus::BAJA->canTransitionTo(UserStatus::BAJA));

        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::ACTIVO));
        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::BAJA));
        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::ELIMINADO));
        $this->assertFalse(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::BAJA_TEMPORAL));

        $this->assertTrue(UserStatus::ELIMINADO->canTransitionTo(UserStatus::ACTIVO));
        $this->assertFalse(UserStatus::ELIMINADO->canTransitionTo(UserStatus::BAJA));
        $this->assertFalse(UserStatus::ELIMINADO->canTransitionTo(UserStatus::BAJA_TEMPORAL));
        $this->assertFalse(UserStatus::ELIMINADO->canTransitionTo(UserStatus::ELIMINADO));
    }

    #[Test]
    public function it_validates_updatable_statuses(): void
    {
        $this->assertTrue(UserStatus::ACTIVO->isUpdatable());

        $this->assertFalse(UserStatus::BAJA_TEMPORAL->isUpdatable());
        $this->assertFalse(UserStatus::BAJA->isUpdatable());
        $this->assertFalse(UserStatus::ELIMINADO->isUpdatable());
    }

    #[Test]
    public function activo_has_the_most_transitions(): void
    {
        $activoTransitions = UserStatus::ACTIVO->allowedTransitions();

        $this->assertCount(3, $activoTransitions);
        $this->assertGreaterThan(
            count(UserStatus::BAJA->allowedTransitions()),
            count($activoTransitions)
        );
        $this->assertGreaterThan(
            count(UserStatus::ELIMINADO->allowedTransitions()),
            count($activoTransitions)
        );
    }

    #[Test]
    public function baja_temporal_is_more_flexible_than_baja(): void
    {
        $bajaTemporalTransitions = UserStatus::BAJA_TEMPORAL->allowedTransitions();
        $bajaTransitions = UserStatus::BAJA->allowedTransitions();

        $this->assertGreaterThan(
            count($bajaTransitions),
            count($bajaTemporalTransitions)
        );
    }

    #[Test]
    public function eliminado_can_only_reactivate_to_activo(): void
    {
        $eliminadoTransitions = UserStatus::ELIMINADO->allowedTransitions();

        $this->assertCount(1, $eliminadoTransitions);
        $this->assertContains(UserStatus::ACTIVO, $eliminadoTransitions);

        $this->assertFalse(UserStatus::ELIMINADO->canTransitionTo(UserStatus::BAJA));
        $this->assertFalse(UserStatus::ELIMINADO->canTransitionTo(UserStatus::BAJA_TEMPORAL));
    }

    #[Test]
    public function allowed_transitions_are_valid_statuses(): void
    {
        $allStatuses = UserStatus::cases();

        foreach ($allStatuses as $status) {
            $transitions = $status->allowedTransitions();

            foreach ($transitions as $transition) {
                $this->assertInstanceOf(UserStatus::class, $transition);
                $this->assertContains($transition, $allStatuses);
            }
        }
    }

    #[Test]
    public function no_self_transitions_allowed(): void
    {
        foreach (UserStatus::cases() as $status) {
            $this->assertFalse(
                $status->canTransitionTo($status),
                "{$status->name} no debería poder transicionar a sí mismo"
            );

            $this->assertNotContains(
                $status,
                $status->allowedTransitions(),
                "allowedTransitions() para {$status->name} no debería incluirse a sí mismo"
            );
        }
    }

    #[Test]
    public function reactivation_from_baja_is_possible(): void
    {
        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::ELIMINADO->canTransitionTo(UserStatus::ACTIVO));
    }

    #[Test]
    public function transition_graph_is_connected(): void
    {
        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ACTIVO));
        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::ACTIVO));
        $this->assertTrue(UserStatus::ELIMINADO->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA));
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA_TEMPORAL));
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::ELIMINADO));
    }

    #[Test]
    public function it_provides_consistent_naming(): void
    {
        $this->assertEquals('ACTIVO', UserStatus::ACTIVO->name);
        $this->assertEquals('activo', UserStatus::ACTIVO->value);

        $this->assertEquals('BAJA_TEMPORAL', UserStatus::BAJA_TEMPORAL->name);
        $this->assertEquals('baja-temporal', UserStatus::BAJA_TEMPORAL->value);

        $this->assertEquals('BAJA', UserStatus::BAJA->name);
        $this->assertEquals('baja', UserStatus::BAJA->value);

        $this->assertEquals('ELIMINADO', UserStatus::ELIMINADO->name);
        $this->assertEquals('eliminado', UserStatus::ELIMINADO->value);
    }

    #[Test]
    public function special_characters_in_values(): void
    {
        $this->assertEquals('baja-temporal', UserStatus::BAJA_TEMPORAL->value);

        $fromValue = UserStatus::from('baja-temporal');
        $this->assertEquals(UserStatus::BAJA_TEMPORAL, $fromValue);
    }

    #[Test]
    public function business_logic_validation(): void
    {
        $this->assertTrue(UserStatus::ACTIVO->isUpdatable());
        $this->assertFalse(UserStatus::BAJA_TEMPORAL->isUpdatable());
        $this->assertFalse(UserStatus::BAJA->isUpdatable());
        $this->assertFalse(UserStatus::ELIMINADO->isUpdatable());

        $bajaTemporalTransitions = UserStatus::BAJA_TEMPORAL->allowedTransitions();
        $bajaTransitions = UserStatus::BAJA->allowedTransitions();
        $this->assertGreaterThan(count($bajaTransitions), count($bajaTemporalTransitions));

        $eliminadoTransitions = UserStatus::ELIMINADO->allowedTransitions();
        $this->assertCount(1, $eliminadoTransitions);
        $this->assertEquals(UserStatus::ACTIVO, $eliminadoTransitions[0]);
    }

    #[Test]
    public function it_can_be_used_in_match_statements(): void
    {
        $status = UserStatus::ACTIVO;

        $result = match($status) {
            UserStatus::ACTIVO => 'active',
            UserStatus::BAJA_TEMPORAL => 'temporary_inactive',
            UserStatus::BAJA => 'inactive',
            UserStatus::ELIMINADO => 'deleted',
        };

        $this->assertEquals('active', $result);
    }

    #[Test]
    public function method_return_types_are_consistent(): void
    {
        $transitions = UserStatus::ACTIVO->allowedTransitions();
        $this->assertIsArray($transitions);
        foreach ($transitions as $transition) {
            $this->assertInstanceOf(UserStatus::class, $transition);
        }

        $this->assertIsBool(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA));

        $this->assertIsBool(UserStatus::ACTIVO->isUpdatable());
    }

    #[Test]
    public function workflow_validation(): void
    {
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA_TEMPORAL));
        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA));
        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::ELIMINADO));
        $this->assertTrue(UserStatus::ELIMINADO->canTransitionTo(UserStatus::ACTIVO));

        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::BAJA));
        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ACTIVO));
    }

    #[Test]
    public function status_hierarchy(): void
    {
        $this->assertTrue(UserStatus::ACTIVO->isUpdatable());

        $this->assertFalse(UserStatus::BAJA_TEMPORAL->isUpdatable());
        $this->assertFalse(UserStatus::BAJA->isUpdatable());

        $this->assertFalse(UserStatus::ELIMINADO->isUpdatable());
        $this->assertTrue(UserStatus::ELIMINADO->canTransitionTo(UserStatus::ACTIVO));
    }

    #[Test]
    public function from_baja_temporal_to_baja_is_allowed_but_not_vice_versa(): void
    {
        $this->assertTrue(UserStatus::BAJA_TEMPORAL->canTransitionTo(UserStatus::BAJA));

        $this->assertFalse(UserStatus::BAJA->canTransitionTo(UserStatus::BAJA_TEMPORAL));
    }
}
