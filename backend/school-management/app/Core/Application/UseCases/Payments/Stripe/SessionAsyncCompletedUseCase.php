<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Mappers\EnumMapper;
use App\Core\Application\Traits\HasPaymentSession;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Exceptions\DomainException;
use Stripe\Checkout\Session;

class SessionAsyncCompletedUseCase
{
   use HasPaymentSession;

    public function execute(Session $obj, string $eventId) {
        try {

            $status = EnumMapper::fromStripe($obj->payment_status);
            $payment= $this->handlePaymentSession($obj, $eventId, PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED);
            return $payment !==null;
        }catch (DomainException $e) {
            return false;

        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;

        } catch (\Exception $e) {
            throw $e;
        }

    }
}
