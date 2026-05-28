<?php

namespace App\Core\Application\UseCases\Payments\Staff\Debts;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Application\DTO\Response\Payment\PaymentValidateResponse;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Mappers\PaymentMapper;
use App\Core\Application\Services\Payments\Staff\PaymentValidationService;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Exceptions\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Core\Application\Mappers\UserMapper as AppUserMapper;
use App\Jobs\ClearStaffCacheJob;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use App\Mail\PaymentValidatedMail;

class ValidatePaymentUseCase{

    public function __construct(
        private PaymentValidationService $validationService
    )
    {
    }
    public function execute(string $search, string $payment_intent_id): PaymentValidateResponse
    {
        try
        {
            [$payment, $student, $wasCreated, $wasReconciled , $reconcileResponse] = DB::transaction(
                fn() => $this->validationService->validateAndGetOrCreatePayment($search, $payment_intent_id)
            );

            if ($wasCreated) {
                $this->processSideEffects($payment, $student);
            }

            return $this->buildResponse($payment, $student, $wasCreated, $wasReconciled, $reconcileResponse);

        }catch (ValidationException $e) {
            logger()->warning("Validación duplicada para pago", [
                'search' => $search,
                'payment_intent_id' => $payment_intent_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            logger()->error("Error al validar pago", [
                'search' => $search,
                'payment_intent_id' => $payment_intent_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

    }

    private function processSideEffects(Payment $payment, User $student): void
    {
        $this->dispatchCacheClearing($payment->user_id);

        $this->sendValidationEmail($payment, $student);

    }

    private function dispatchCacheClearing(int $userId): void
    {
        ClearStudentCacheJob::dispatch($userId)
            ->onQueue('cache');

        ClearStaffCacheJob::dispatch()
            ->onQueue('cache');
    }

    private function sendValidationEmail(Payment $payment, User $student): void
    {
        $data = [
            'recipientName' => $student->fullName(),
            'recipientEmail' => $student->email,
            'concept_name' => $payment->concept_name,
            'amount' => $payment->amount,
            'amount_received' => $payment->amount_received,
            'payment_method_detail' => $payment->payment_method_details ?? [],
            'status' => $payment->status->value,
            'url' => $payment->url ?? null,
            'payment_intent_id' => $payment->payment_intent_id,
        ];

        $mail = new PaymentValidatedMail(
            MailMapper::toPaymentValidatedEmailDTO($data)
        );

        SendMailJob::forUser($mail, $student->email, 'validate_payment')
            ->onQueue('emails');
    }

    private function buildResponse(Payment $payment, User $student, bool $wasCreated, bool $wasReconciled, ReconciliationResult $result): PaymentValidateResponse
    {
        $metadata = [
            'wasCreated' => $wasCreated,
            'wasReconciled' => $wasReconciled,
            'message' => $wasCreated
                ? 'Pago creado con éxito, se realizó el proceso correctamente.'
                : 'Pago reconciliado, se realizó el proceso correctamente.',
            'reconciliationResult' => $wasReconciled ? $result->toArray() : null,
        ];
        return PaymentMapper::toPaymentValidateResponse(
            AppUserMapper::toDataResponse($student),
            PaymentMapper::toPaymentDataResponse($payment),
            $metadata
        );
    }
}
