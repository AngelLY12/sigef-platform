<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Mappers\EnumMapper;
use App\Core\Application\Traits\HasPaymentSession;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\DomainException;

class SessionCompletedUseCase
{

    use HasPaymentSession;

    /**
     * @throws \Exception
     */
    public function execute($obj, string $eventId)
    {
        try {
            if (!isset($obj->mode)) {
                logger()->warning("Evento de sesión sin 'mode'. session_id={$obj->id}");
                return true;
            }
            if ($obj->mode === 'payment') {
                $status = EnumMapper::fromStripe($obj->payment_status);
                $payment=$this->handlePaymentSession($obj, [
                    'payment_intent_id' => $obj->payment_intent,
                    'status' => $status,
                ], $eventId, PaymentEventType::WEBHOOK_SESSION_COMPLETED);
                return $payment !== null;
            }
            if ($obj->mode === 'setup') {
                return $this->finalizeSetupSession($obj);
            }
            logger()->info("Sesión ignorada en webhook. session_id={$obj->id}");
            return true;
        }catch (DomainException $e) {
            logger()->warning("Excepción de dominio en webhook: " . $e->getMessage(), [
                'exception' => get_class($e),
                'use_case' => static::class
            ]);
            return false;

        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->warning("Excepción de validación en webhook: " . $e->getMessage());
            return false;

        } catch (\Exception $e) {
            logger()->error("Error inesperado en webhook: " . $e->getMessage(), [
                'exception' => get_class($e),
                'use_case' => static::class,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

}
