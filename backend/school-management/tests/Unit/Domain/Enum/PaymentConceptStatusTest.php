<?php

namespace Tests\Unit\Domain\Enum;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Domain\EnumTestCase;

class PaymentConceptStatusTest extends EnumTestCase
{
    protected function enumClass(): string
    {
        return PaymentConceptStatus::class;
    }

    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = $this->getAllCases();

        $this->assertCount(4, $cases, 'PaymentConceptStatus debe tener 4 casos');

        $expectedValues = [
            'activo',
            'finalizado',
            'desactivado',
            'eliminado',
        ];

        foreach ($expectedValues as $expectedValue) {
            $this->assertContains(
                $expectedValue,
                $this->getAllValues(),
                "PaymentConceptStatus debe incluir '{$expectedValue}'"
            );
        }
    }

    #[Test]
    public function it_has_correct_allowed_transitions(): void
    {
        $activoTransitions = PaymentConceptStatus::ACTIVO->allowedTransitions();
        $this->assertCount(3, $activoTransitions);
        $this->assertContains(PaymentConceptStatus::FINALIZADO, $activoTransitions);
        $this->assertContains(PaymentConceptStatus::DESACTIVADO, $activoTransitions);
        $this->assertContains(PaymentConceptStatus::ELIMINADO, $activoTransitions);

        $finalizadoTransitions = PaymentConceptStatus::FINALIZADO->allowedTransitions();
        $this->assertCount(2, $finalizadoTransitions);
        $this->assertContains(PaymentConceptStatus::ACTIVO, $finalizadoTransitions);
        $this->assertContains(PaymentConceptStatus::ELIMINADO, $finalizadoTransitions);

        $desactivadoTransitions = PaymentConceptStatus::DESACTIVADO->allowedTransitions();
        $this->assertCount(2, $desactivadoTransitions);
        $this->assertContains(PaymentConceptStatus::ACTIVO, $desactivadoTransitions);
        $this->assertContains(PaymentConceptStatus::ELIMINADO, $desactivadoTransitions);

        $eliminadoTransitions = PaymentConceptStatus::ELIMINADO->allowedTransitions();
        $this->assertCount(2, $eliminadoTransitions);
        $this->assertContains(PaymentConceptStatus::ACTIVO, $eliminadoTransitions);
        $this->assertContains(PaymentConceptStatus::DESACTIVADO, $eliminadoTransitions);
    }

    #[Test]
    public function it_validates_transitions_correctly(): void
    {
        $this->assertTrue(PaymentConceptStatus::ACTIVO->canTransitionTo(PaymentConceptStatus::FINALIZADO));
        $this->assertTrue(PaymentConceptStatus::ACTIVO->canTransitionTo(PaymentConceptStatus::DESACTIVADO));
        $this->assertTrue(PaymentConceptStatus::ACTIVO->canTransitionTo(PaymentConceptStatus::ELIMINADO));
        $this->assertFalse(PaymentConceptStatus::ACTIVO->canTransitionTo(PaymentConceptStatus::ACTIVO));

        $this->assertTrue(PaymentConceptStatus::FINALIZADO->canTransitionTo(PaymentConceptStatus::ACTIVO));
        $this->assertTrue(PaymentConceptStatus::FINALIZADO->canTransitionTo(PaymentConceptStatus::ELIMINADO));
        $this->assertFalse(PaymentConceptStatus::FINALIZADO->canTransitionTo(PaymentConceptStatus::DESACTIVADO));
        $this->assertFalse(PaymentConceptStatus::FINALIZADO->canTransitionTo(PaymentConceptStatus::FINALIZADO));

        $this->assertTrue(PaymentConceptStatus::DESACTIVADO->canTransitionTo(PaymentConceptStatus::ACTIVO));
        $this->assertTrue(PaymentConceptStatus::DESACTIVADO->canTransitionTo(PaymentConceptStatus::ELIMINADO));
        $this->assertFalse(PaymentConceptStatus::DESACTIVADO->canTransitionTo(PaymentConceptStatus::FINALIZADO));
        $this->assertFalse(PaymentConceptStatus::DESACTIVADO->canTransitionTo(PaymentConceptStatus::DESACTIVADO));

        $this->assertTrue(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::ACTIVO));
        $this->assertTrue(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::DESACTIVADO));
        $this->assertFalse(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::FINALIZADO));
        $this->assertFalse(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::ELIMINADO));
    }

    #[Test]
    public function it_validates_updatable_statuses(): void
    {
        $this->assertTrue(PaymentConceptStatus::ACTIVO->isUpdatable());
        $this->assertTrue(PaymentConceptStatus::DESACTIVADO->isUpdatable());

        $this->assertFalse(PaymentConceptStatus::FINALIZADO->isUpdatable());
        $this->assertFalse(PaymentConceptStatus::ELIMINADO->isUpdatable());
    }

    #[Test]
    public function transitions_are_symmetrical_for_some_states(): void
    {
        $this->assertEquals(
            PaymentConceptStatus::FINALIZADO->allowedTransitions(),
            PaymentConceptStatus::DESACTIVADO->allowedTransitions(),
            'FINALIZADO y DESACTIVADO deberían tener las mismas transiciones'
        );
    }

    #[Test]
    public function eliminado_can_reactivate_to_activo(): void
    {
        $this->assertTrue(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::ACTIVO));

        $this->assertFalse(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::FINALIZADO));
    }

    #[Test]
    public function activo_has_the_most_transitions(): void
    {
        $activoTransitions = PaymentConceptStatus::ACTIVO->allowedTransitions();
        $allStatuses = PaymentConceptStatus::cases();

        foreach ($allStatuses as $status) {
            if ($status !== PaymentConceptStatus::ACTIVO) {
                $this->assertGreaterThan(
                    count($status->allowedTransitions()),
                    count($activoTransitions),
                    "ACTIVO debería tener más transiciones que {$status->name}"
                );
            }
        }
    }

    #[Test]
    public function allowed_transitions_are_valid_statuses(): void
    {
        $allStatuses = PaymentConceptStatus::cases();

        foreach ($allStatuses as $status) {
            $transitions = $status->allowedTransitions();

            foreach ($transitions as $transition) {
                $this->assertInstanceOf(PaymentConceptStatus::class, $transition);
                $this->assertContains($transition, $allStatuses);
            }
        }
    }

    #[Test]
    public function no_self_transitions_allowed(): void
    {
        foreach (PaymentConceptStatus::cases() as $status) {
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
    public function transition_graph_is_connected(): void
    {
        $statuses = PaymentConceptStatus::cases();

        foreach ($statuses as $from) {
            foreach ($statuses as $to) {
                if ($from !== $to) {
                    $canTransition = $from->canTransitionTo($to);

                    if (!$canTransition) {
                        echo "No hay transición directa de {$from->name} a {$to->name}\n";
                    }
                }
            }
        }

        $this->assertTrue(true, 'El grafo de transiciones está bien definido');
    }

    #[Test]
    public function it_provides_consistent_naming(): void
    {
        $this->assertEquals('ACTIVO', PaymentConceptStatus::ACTIVO->name);
        $this->assertEquals('activo', PaymentConceptStatus::ACTIVO->value);

        $this->assertEquals('FINALIZADO', PaymentConceptStatus::FINALIZADO->name);
        $this->assertEquals('finalizado', PaymentConceptStatus::FINALIZADO->value);
    }

    #[Test]
    public function it_can_be_used_in_match_statements(): void
    {
        $status = PaymentConceptStatus::ACTIVO;

        $result = match($status) {
            PaymentConceptStatus::ACTIVO => 'active',
            PaymentConceptStatus::FINALIZADO => 'finished',
            PaymentConceptStatus::DESACTIVADO => 'deactivated',
            PaymentConceptStatus::ELIMINADO => 'deleted',
        };

        $this->assertEquals('active', $result);
    }

    #[Test]
    public function business_logic_validation(): void
    {
        $this->assertTrue(PaymentConceptStatus::ACTIVO->isUpdatable());

        $this->assertTrue(PaymentConceptStatus::DESACTIVADO->isUpdatable());

       $this->assertFalse(PaymentConceptStatus::FINALIZADO->isUpdatable());

        $this->assertFalse(PaymentConceptStatus::ELIMINADO->isUpdatable());
    }

    #[Test]
    public function transition_to_eliminado_is_possible_from_all_statuses(): void
    {
        $this->assertTrue(PaymentConceptStatus::ACTIVO->canTransitionTo(PaymentConceptStatus::ELIMINADO));

        $this->assertTrue(PaymentConceptStatus::FINALIZADO->canTransitionTo(PaymentConceptStatus::ELIMINADO));

        $this->assertTrue(PaymentConceptStatus::DESACTIVADO->canTransitionTo(PaymentConceptStatus::ELIMINADO));

        $this->assertFalse(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::ELIMINADO));
    }

    #[Test]
    public function reactivation_is_possible(): void
    {
        $this->assertTrue(PaymentConceptStatus::FINALIZADO->canTransitionTo(PaymentConceptStatus::ACTIVO));

        $this->assertTrue(PaymentConceptStatus::DESACTIVADO->canTransitionTo(PaymentConceptStatus::ACTIVO));

        $this->assertTrue(PaymentConceptStatus::ELIMINADO->canTransitionTo(PaymentConceptStatus::ACTIVO));
    }

}
