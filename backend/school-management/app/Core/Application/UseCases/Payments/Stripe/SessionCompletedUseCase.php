<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Mappers\EnumMapper;
use App\Core\Application\Traits\HasPaymentSession;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Exceptions\DomainException;
use Stripe\Checkout\Session;

class SessionCompletedUseCase
{

    use HasPaymentSession;

    /**
     * @throws \Exception
     */
    public function execute(Session $obj, string $eventId)
    {
        try {
            if (!isset($obj->mode)) {
                return true;
            }
            if ($obj->mode === 'payment') {
                $status = EnumMapper::fromStripe($obj->payment_status);
                $payment=$this->handlePaymentSession($obj, $eventId, PaymentEventType::WEBHOOK_SESSION_COMPLETED);
                return $payment !== null;
            }
            if ($obj->mode === 'setup') {
                return $this->finalizeSetupSession($obj);
            }
            return true;
        }catch (DomainException $e) {
            return false;

        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;

        } catch (\Exception $e) {
            throw $e;
        }
    }

}
