<?php
namespace App\Core\Infraestructure\Repositories\Stripe;

use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Domain\Utils\Validators\StripeValidator;
use App\Exceptions\ServerError\StripeGatewayException;
use App\Exceptions\Validation\PayoutValidationException;
use InvalidArgumentException;
use Stripe\Balance;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\RateLimitException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Payout;
use Stripe\Stripe;

class StripeGateway implements StripeGatewayInterface
{
    public function __construct(
    )
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createStripeUser(User $user): string
    {
        StripeValidator::validateUserForStripe($user);

        try{
            if ($user->stripe_customer_id) {
                return $user->stripe_customer_id;
            }

            $existingCustomers = Customer::all(['email' => $user->email, 'limit' => 1]);

            if (count($existingCustomers->data) > 0) {
                return $existingCustomers->data[0]->id;
            }

            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->fullName(),
            ]);

            return $customer->id;
        }catch (\InvalidArgumentException $e) {
            throw $e;
        }catch(ApiErrorException $e){
            logger()->error("Stripe error creating customer: " . $e->getMessage());
            throw new StripeGatewayException("Error al crear el cliente en Stripe", 500);

        }

    }

    public function createSetupSession(string $customerId): Session
    {
        try{
            return Session::create([
                'mode' => 'setup',
                'expires_at' => time() + 3600,
                'payment_method_types' => ['card'],
                'customer' => $customerId,
                'success_url' => config('app.frontend_url') . '/Estudiante/Tarjetas?result=added',
                'cancel_url' => config('app.frontend_url') . '/Estudiante/Tarjetas?result=canceled',
            ]);
        }catch(ApiErrorException $e){
            logger()->error("Stripe error setupSession: " . $e->getMessage());
            throw new StripeGatewayException("Error al crear la sesión setup", 500);
        }catch (RateLimitException $e) {
            logger()->error("Rate limit hit: " . $e->getMessage());
            throw new StripeGatewayException("Limite de peticiones superado, espera un momento", 500);
        }

    }

     public function createCheckoutSession(string $customerId, PaymentConcept $paymentConcept, string $amount, int $userId): Session
    {
        try{
            $sessionData = [
            'mode' => 'payment',
            'customer' => $customerId,
            'customer_update' => ['address' => 'auto'],
            'expires_at' => time() + 3600,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => ['name' => $paymentConcept->concept_name],
                    'unit_amount' => Money::from($paymentConcept->amount)->toMinorUnits(),
                ],
                'quantity' => 1,
            ]],
            'payment_method_types' => ['card', 'oxxo', 'customer_balance'],
            'metadata' => [
                'payment_concept_id' => $paymentConcept->id,
                'concept_name' => $paymentConcept->concept_name,
                'user_id' => $userId,
            ],
            'payment_method_options' => [
                'card' => [
                    'request_three_d_secure' => 'automatic',
                    'setup_future_usage' => 'off_session'
                ],
                'customer_balance' => [
                    'funding_type' => 'bank_transfer',
                    'bank_transfer' => ['type' => 'mx_bank_transfer'],
                ],
                'oxxo' => [
                    'expires_after_days' => 2
                ],
            ],
            'saved_payment_method_options' => ['payment_method_save' => 'enabled'],
            'success_url' => config('app.frontend_url') . '/Estudiante/Historial?result=paid',
            'cancel_url' => config('app.frontend_url') . '/Estudiante/Adeudos?result=canceled',
        ];

        return Session::create($sessionData);

        }catch(ApiErrorException $e){
            logger()->error("Stripe error checkout session: " . $e->getMessage());
            throw new StripeGatewayException("Error al crear la sesión", 500);
        }catch (RateLimitException $e) {
            logger()->error("Rate limit hit: " . $e->getMessage());
            throw new StripeGatewayException("Se alcanzo el limite de intentos, espera un momento", 500);
        }

    }

    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        StripeValidator::validateStripeId($paymentMethodId,'pm','método de pago');
        try{
            $pm = StripePaymentMethod::retrieve($paymentMethodId);
            $pm->detach();
            return true;
        }catch (ApiErrorException $e) {
            logger()->error("Stripe error detaching PaymentMethod: " . $e->getMessage());
            throw new StripeGatewayException("Error eliminando el método de pago", 500);
        }

    }
    public function expireSessionIfPending(string $sessionId): bool
    {
        StripeValidator::validateStripeId($sessionId,'cs','ID de la sesión');
        try {
            $session = Session::retrieve($sessionId);

            if (in_array($session->status, ['expired', 'complete'])) {
                return false;
            }
            $createdAt = $session->created;
            $oneHourAgo = time() - 3600;

            if ($createdAt <= $oneHourAgo) {
                return false;
            }

            $safeToExpireStatuses = [
                PaymentStatus::UNPAID->value,
                PaymentStatus::REQUIRES_ACTION->value,
            ];

            if (!in_array($session->payment_status, $safeToExpireStatuses)) {
                return false;
            }

            if (!empty($session->payment_intent)) {
                try {
                    $paymentIntent = PaymentIntent::retrieve($session->payment_intent);

                    $cancelableStatuses = [
                        'requires_payment_method',
                        'requires_confirmation',
                        'requires_action',
                        'processing'
                    ];

                    if (in_array($paymentIntent->status, $cancelableStatuses)) {
                        $paymentIntent->cancel([
                            'cancellation_reason' => 'abandoned'
                        ]);
                        logger()->info("PaymentIntent cancelado", [
                            'payment_intent' => $session->payment_intent,
                            'previous_status' => $paymentIntent->status
                        ]);
                    }
                } catch (\Exception $e) {
                    logger()->warning("No se pudo cancelar PaymentIntent: " . $e->getMessage());
                }
            }
            $session->expire();
            return true;
        } catch (\Exception $e) {
            logger()->warning("No se pudo expirar la sesión {$sessionId}: " . $e->getMessage());
            return false;
        }
    }

    public function createPayout(): array
    {
        try
        {
            $balance = Balance::retrieve();
            $totalAvailableMxn = Money::from('0');
            foreach ($balance->available as $item) {
                if ($item->currency === 'mxn') {
                    $totalAvailableMxn = $totalAvailableMxn->add((string)$item->amount)->divide('100');
                }
            }

            $minimumPayout = Money::from('100.00');
            if ($totalAvailableMxn->isLessThan($minimumPayout)) {
                throw new PayoutValidationException("Fondos insuficientes. Disponible: $" .
                    $totalAvailableMxn->finalize() . " MXN. " .
                    "Mínimo requerido: $100.00 MXN");
            }
            $payout = Payout::create([
                'amount' => (int)$totalAvailableMxn->multiply('100')->finalize(0),
                'currency' => 'mxn',
                'description' => 'Payout manual de la escuela',
            ]);

            logger()->info('Payout creado exitosamente', [
                'payout_id' => $payout->id,
                'amount' => Money::from((string) $payout->amount)
                    ->divide('100')
                    ->finalize(),
                'arrival_date' => date('Y-m-d', $payout->arrival_date),
            ]);


            return [
                'success' => true,
                'payout_id' => $payout->id,
                'amount' => Money::from((string) $payout->amount)
                    ->divide('100')
                    ->finalize(),
                'currency' => $payout->currency,
                'arrival_date' => date('Y-m-d', $payout->arrival_date),
                'status' => $payout->status,
                'available_before_payout' => $totalAvailableMxn->finalize(),
            ];

        }
        catch (ApiErrorException $e) {
            logger()->error('Error de Stripe al crear payout', [
                'error' => $e->getMessage(),
            ]);
            throw new StripeGatewayException('Error al crear payout: ' . $e->getMessage());
        }
        catch (\Exception $e) {
            logger()->error('Error inesperado al crear payout', [
                'error' => $e->getMessage(),
            ]);
            throw new StripeGatewayException('Error inesperado: ' . $e->getMessage());
        }
    }
}
