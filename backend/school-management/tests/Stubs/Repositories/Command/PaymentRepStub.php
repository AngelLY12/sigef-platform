<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentRepStub implements PaymentRepInterface
{
    private bool $throwDatabaseError = false;
    private array $payments = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Pagos de prueba iniciales
        $this->payments = [
            1 => new Payment(
                'Matrícula Semestral',
                '5000.00',
                PaymentStatus::PAID,
                ['Tarjeta de crédito'],
                1,
                1,
                10,
                null,
                null,
                '5000.00',
                'pi_123',
                null,
                'cs_123',
                Carbon::now()->subDays(5)
            ),
            2 => new Payment(
                'Inscripción',
                '2000.00',
                PaymentStatus::DEFAULT,
                [],
                2,
                2,
                11,
                null,
                null,
                null,
                null,
                null,
                'cs_456',
                Carbon::now()->subHours(2)
            ),
            3 => new Payment(
                'Pago parcial',
                '1000.00',
                PaymentStatus::UNDERPAID,
                [],
                3,
                3,
                12,
                null,
                null,
                '800.00',
                'pi_789',
                null,
                'cs_789',
                Carbon::now()->subMinutes(30)
            ),
        ];
        $this->nextId = 4;
    }

    public function create(Payment $payment): Payment
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $id = $payment->id ?? $this->nextId++;

        $newPayment = new Payment(
            $payment->concept_name,
            $payment->amount,
            $payment->status,
            $payment->payment_method_details,
            $id,
            $payment->user_id,
            $payment->payment_concept_id,
            $payment->payment_method_id,
            $payment->stripe_payment_method_id,
            $payment->amount_received,
            $payment->payment_intent_id,
            $payment->url,
            $payment->stripe_session_id,
            $payment->created_at ?? Carbon::now()
        );

        $this->payments[$id] = $newPayment;

        return $newPayment;
    }

    public function update(int $paymentId, array $fields): Payment
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->payments[$paymentId])) {
            throw new ModelNotFoundException('Payment not found');
        }

        $existingPayment = $this->payments[$paymentId];

        // Crear nuevo pago con campos actualizados
        $updatedPayment = new Payment(
            $fields['concept_name'] ?? $existingPayment->concept_name,
            $fields['amount'] ?? $existingPayment->amount,
            $fields['status'] ?? $existingPayment->status,
            $fields['payment_method_details'] ?? $existingPayment->payment_method_details,
            $existingPayment->id,
            $fields['user_id'] ?? $existingPayment->user_id,
            $fields['payment_concept_id'] ?? $existingPayment->payment_concept_id,
            $fields['payment_method_id'] ?? $existingPayment->payment_method_id,
            $fields['stripe_payment_method_id'] ?? $existingPayment->stripe_payment_method_id,
            $fields['amount_received'] ?? $existingPayment->amount_received,
            $fields['payment_intent_id'] ?? $existingPayment->payment_intent_id,
            $fields['url'] ?? $existingPayment->url,
            $fields['stripe_session_id'] ?? $existingPayment->stripe_session_id,
            $existingPayment->created_at
        );

        $this->payments[$paymentId] = $updatedPayment;

        return $updatedPayment;
    }

    public function delete(int $paymentId): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->payments[$paymentId])) {
            throw new ModelNotFoundException('Payment not found');
        }

        unset($this->payments[$paymentId]);
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function addPayment(Payment $payment): self
    {
        $id = $payment->id ?? $this->nextId++;

        if ($payment->id === null) {
            $paymentWithId = new Payment(
                $payment->concept_name,
                $payment->amount,
                $payment->status,
                $payment->payment_method_details,
                $id,
                $payment->user_id,
                $payment->payment_concept_id,
                $payment->payment_method_id,
                $payment->stripe_payment_method_id,
                $payment->amount_received,
                $payment->payment_intent_id,
                $payment->url,
                $payment->stripe_session_id,
                $payment->created_at ?? Carbon::now()
            );
            $this->payments[$id] = $paymentWithId;
        } else {
            $this->payments[$id] = $payment;
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        }

        return $this;
    }

    public function getPayment(int $id): ?Payment
    {
        return $this->payments[$id] ?? null;
    }

    public function getPaymentsCount(): int
    {
        return count($this->payments);
    }

    public function clearPayments(): self
    {
        $this->payments = [];
        $this->nextId = 1;
        return $this;
    }

    public function getAllPayments(): array
    {
        return $this->payments;
    }

    public function getUserPayments(int $userId): array
    {
        return array_filter($this->payments, function($payment) use ($userId) {
            return $payment->user_id === $userId;
        });
    }

    public function getPaymentsByStatus(PaymentStatus $status): array
    {
        return array_filter($this->payments, function($payment) use ($status) {
            return $payment->status === $status;
        });
    }
}
