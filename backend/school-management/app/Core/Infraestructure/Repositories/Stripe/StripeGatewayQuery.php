<?php

namespace App\Core\Infraestructure\Repositories\Stripe;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Domain\Utils\Validators\StripeValidator;
use App\Exceptions\ServerError\StripeGatewayException;
use App\Exceptions\Validation\ValidationException;
use Stripe\Balance;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Payout;
use Stripe\SetupIntent;
use Stripe\Stripe;

class StripeGatewayQuery implements StripeGatewayQueryInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    public function getSetupIntentFromSession(string $sessionId)
    {
        StripeValidator::validateStripeId($sessionId,'cs','ID de la sesión');
        try{
            $session = Session::retrieve($sessionId);
            if (empty($session->setup_intent)) return null;

            return SetupIntent::retrieve($session->setup_intent);
        }catch (ApiErrorException $e) {
            logger()->error("Stripe error retrieving setup_intent from session: " . $e->getMessage());
            throw new StripeGatewayException("Error trayendo el intent de la sesión", 500);
        }
    }

    public function retrievePaymentMethod(string $paymentMethodId)
    {
        StripeValidator::validateStripeId($paymentMethodId,'pm','método de pago');
        try {
            return StripePaymentMethod::retrieve($paymentMethodId);
        } catch (ApiErrorException $e) {
            logger()->error("Stripe error retrieving PaymentMethod {$paymentMethodId}: " . $e->getMessage());
            throw new StripeGatewayException("Error obteniendo el método de pago", 500);
        }
    }

    public function getIntentAndCharge(string $paymentIntentId): array
    {
        StripeValidator::validateStripeId($paymentIntentId,'pi','payment intent');
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId, [
                'expand' => ['charges', 'latest_charge'],
            ]);

            if (!$intent) {
                throw new ValidationException("Intent no encontrado en Stripe: {$paymentIntentId}");
            }

            $charge = $intent->charges->data[0] ?? null;
            if (!$charge && isset($intent->latest_charge) && $intent->latest_charge) {
                $charge = Charge::retrieve($intent->latest_charge);
            }

            logger()->info("Intent {$paymentIntentId}: status={$intent->status}, charge_id=" . ($charge->id ?? 'null'));

            return [$intent, $charge];
        } catch (ApiErrorException $e) {
            logger()->error("Stripe error retrieving intent/charge: " . $e->getMessage());
            throw new StripeGatewayException("Error obteniendo los datos", 500);
        }
    }


    public function getStudentPaymentsFromStripe(User $user, ?int $year): array
    {
        $params = [
            'limit' => 100,
            'customer' => $user->stripe_customer_id,
        ];

        if ($year) {
            $params['created'] = [
                'gte' => strtotime("{$year}-01-01 00:00:00"),
                'lte' => strtotime("{$year}-12-31 23:59:59"),
            ];
        }
        try {
            $allSessions = [];
            $lastId = null;
            do {
                if ($lastId) {
                    $params['starting_after'] = $lastId;
                }

                $sessions = Session::all($params);

                $allSessions = [... $allSessions, ...$sessions->data];

                $lastId = end($sessions->data)->id ?? null;

            } while ($lastId && count($sessions->data) === $params['limit']);
            $paymentIntentIds = [];
            foreach ($allSessions as $session) {
                if ($session->payment_status === 'paid' && $session->payment_intent) {
                    $paymentIntentIds[] = $session->payment_intent;
                }
            }
            $paymentIntents = [];
            if (!empty($paymentIntentIds)) {
                $uniqueIds = array_unique($paymentIntentIds);

                foreach (array_chunk($uniqueIds, 100) as $chunk) {
                    $batch = PaymentIntent::all([
                        'ids' => $chunk,
                        'expand' => ['data.charges'],
                        'limit' => 100,
                    ]);

                    foreach ($batch->data as $pi) {
                        $paymentIntents[$pi->id] = $pi;
                    }
                }
            }

            $paymentsWithDetails = [];
            foreach ($allSessions as $session) {
                if ($session->payment_status !== 'paid' || !$session->payment_intent) {
                    continue;
                }
                $amountReceived = 0;
                $paymentStatus = $session->payment_status;
                $receiptUrl = null;

                if ($session->payment_intent && $session->payment_status === 'paid') {
                    $pi = $paymentIntents[$session->payment_intent] ?? null;
                    if ($pi) {
                        $amountReceived = $pi->amount_received ?? 0;
                        $paymentStatus = $pi->status;

                        if (!empty($pi->charges->data[0])) {
                            $receiptUrl = $pi->charges->data[0]->receipt_url ?? null;
                        }
                    }
                }

                $session->amount_received = $amountReceived;
                $session->payment_status_detailed = $paymentStatus;
                $session->receipt_url = $receiptUrl;
                $paymentsWithDetails[] = $session;
            }

            return $paymentsWithDetails;
        } catch (ApiErrorException $e) {
            logger()->error("Stripe error fetching sessions: " . $e->getMessage());
            throw new StripeGatewayException("Error obteniendo los pagos del estudiante", 500);
        }
    }

    public function getPaymentIntentFromSession(string $sessionId): PaymentIntent
    {
        StripeValidator::validateStripeId($sessionId,'cs','ID de la sesión');
        try {
            $session = Session::retrieve($sessionId);
            if (!$session->payment_intent) {
                throw new ValidationException("Session sin payment_intent: {$sessionId}");
            }

            return PaymentIntent::retrieve($session->payment_intent);
        } catch (ApiErrorException $e) {
            logger()->error("Stripe error retrieving payment intent from session: " . $e->getMessage());
            throw new StripeGatewayException("Error obteniendo los datos", 500);
        }
    }

    public function getBalanceFromStripe(): array
    {
        $balance = Balance::retrieve();
        $available = [];
        foreach ($balance->available as $a) {
            $available[] = [
                'amount' => Money::from((string) $a->amount)->divide('100')->finalize(),
                'source_types' => $a->source_types->toArray(),
            ];
        }

        $pending = [];
        foreach ($balance->pending as $p) {
            $pending[] = [
                'amount' => Money::from((string) $p->amount)->divide('100')->finalize(),
                'source_types' => $p->source_types->toArray(),
            ];
        }
        return [
            'available' => $available,
            'pending' => $pending
        ];
    }

    public function getPayoutsFromStripe(bool $onlyThisYear = false): array
    {
        $params = [
            'limit' => 100,
        ];

        if ($onlyThisYear) {
            $currentYear = date('Y');
            $params['created'] = [
                'gte' => strtotime("$currentYear-01-01"),
                'lte' => strtotime("$currentYear-12-31 23:59:59")
            ];
        }

        $totalPayouts = Money::from('0');
        $totalFees = Money::from('0');
        $byMonth = [];
        $hasMore = true;
        $lastId = null;


        while ($hasMore) {
            if ($lastId) {
                $params['starting_after'] = $lastId;
            }

            $payouts = Payout::all($params);

            foreach ($payouts->data as $payout) {
                $amount = Money::from((string) $payout->amount)->divide('100');
                $month = date('Y-m', $payout->arrival_date);

                $totalPayouts = $totalPayouts->add($amount);

                if (!isset($byMonth[$month])) {
                    $byMonth[$month] = Money::from('0');

                }

                $byMonth[$month] = $byMonth[$month]->add($amount);
            }

            $hasMore = $payouts->has_more;
            $lastId = !empty($payouts->data) ? end($payouts->data)->id : null;
        }

        return [
            'total' => $totalPayouts->finalize(),
            'by_month' => array_map(
                fn($m) => $m->finalize(),
                $byMonth
            ),
        ];
    }

    public function getFeesFromStripe(bool $onlyThisYear = false): array
    {
        $params = [
            'limit' => 250,
        ];

        if ($onlyThisYear) {
            $currentYear = date('Y');
            $params['created'] = [
                'gte' => strtotime("$currentYear-01-01"),
                'lte' => strtotime("$currentYear-12-31 23:59:59")
            ];
        }

        $totalFees = Money::from('0');
        $byMonth = [];
        $hasMore = true;
        $lastId = null;

        while ($hasMore) {
            if ($lastId) {
                $params['starting_after'] = $lastId;
            }

            $transactions = BalanceTransaction::all($params);

            foreach ($transactions->data as $transaction) {
                $fee = Money::from((string) abs($transaction->fee))->divide('100');
                $month = date('Y-m', $transaction->created);

                $totalFees = $totalFees->add($fee);

                if (!isset($byMonth[$month])) {
                    $byMonth[$month] = Money::from('0');
                }

                $byMonth[$month] = $byMonth[$month]->add($fee);
            }

            $hasMore = $transactions->has_more;
            $lastId = !empty($transactions->data) ? end($transactions->data)->id : null;
        }

        return [
            'total_fees' => $totalFees->finalize(),
            'by_month' => array_map(fn($m) => $m->finalize(), $byMonth)
        ];
    }

    public function getIntentsAndChargesBatch(array $paymentIntentIds): array
    {
        if (empty($paymentIntentIds)) {
            return [];
        }

        $results = [];

        $uniqueIds = array_unique($paymentIntentIds);

        $chunks = array_chunk($uniqueIds, 50);

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $intentId) {
                try {
                    $results[$intentId] = $this->getIntentAndCharge($intentId);
                } catch (\Exception $e) {
                    logger()->warning("No se pudo obtener intent {$intentId}: " . $e->getMessage());
                    continue;
                }
            }

            if ($chunkIndex < count($chunks) - 1 && count($chunks)-1) {
                usleep(100000);
            }
        }

        return $results;
    }

    public function countSessionsByMetadata(array $metadata, string $status): int
    {
        $queryParts = [];
        foreach ($metadata as $key => $value) {
            $queryParts[] = "metadata['{$key}']:'{$value}'";
        }

        $queryParts[] = "status:'{$status}'";
        $query = implode(' AND ', $queryParts);

        try {
            $result = \Stripe\Checkout\Session::search([
                'query' => $query,
            ]);

            return $result->total_count;

        } catch (\Exception $e) {
            logger()->error("Error contando sesiones por estado: " . $e->getMessage());
            return 0;
        }
    }

    public function getSessionsByMetadata(array $metadataFilters, string $status, int $limit = 100): array
    {
        $sessions = [];
        $params = [];
        foreach ($metadataFilters as $key => $value) {
            $params[] ="metadata['{$key}']:'{$value}'";
        }

        $params[] = "status:'{$status}'";
        $params = implode(' AND ', $params);

        try {
            $stripeSessions = \Stripe\Checkout\Session::search([
                'query' => $params,
                'limit' => $limit,
                'expand' => "data.payment_intent"
            ]);

            foreach ($stripeSessions->data as $session) {
                $sessions[] = [
                    'id' => $session->id,
                    'payment_intent_id' => $session->payment_intent?->id,
                    'amount_total' => $session->amount_total,
                    'amount_received' => $session->payment_intent?->amount_received,
                    'status' => $session->payment_status,
                    'metadata' => (array)$session->metadata,
                    'created' => $session->created,
                    'customer' => $session->customer ?? null,
                ];
            }
            logger()->debug("Encontradas " . count($sessions) . " sesiones con filtros", [
                'filters' => $metadataFilters,
                'session_ids' => array_column($sessions, 'id')
            ]);

            return $sessions;
        } catch (\Exception $e) {
            logger()->error("Error obteniendo sesiones de Stripe: " . $e->getMessage());
            return [];
        }
    }

}
