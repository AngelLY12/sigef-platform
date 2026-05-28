<?php

namespace App\Http\Controllers\Staff;

use App\Core\Application\Services\Payments\Staff\DebtsServiceFacades;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\Staff\GetStripePaymentsRequest;
use App\Http\Requests\Payments\Staff\PaginationWithSearchRequest;
use App\Http\Requests\Payments\Staff\ValidatePaymentRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Debts",
 *     description="Endpoints para la gestión y consulta de pagos pendinetes y validación de los mismos cuando haya un error de registro"
 * )
 */
class DebtsController extends Controller
{
    protected DebtsServiceFacades $debtsService;

    public function __construct(DebtsServiceFacades $debtsService)
    {
        $this->debtsService=$debtsService;

    }
    public function index(PaginationWithSearchRequest $request)
    {
        $search = $request->validated()['search'] ?? null;
        $perPage = $request->validated()['perPage'] ?? 15;
        $page = $request->validated()['page'] ?? 1;
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $pendingPayments = $this->debtsService->showAllpendingPayments($search, $perPage, $page, $forceRefresh);

        return Response::success(
            ['pending_payments' => $pendingPayments],
            empty($pendingPayments->items) ? 'No hay pagos pendientes registrados.' : null
        );
    }
   public function validatePayment(ValidatePaymentRequest $request)
    {
        $data = $request->validated();

        $validatedPayment = $this->debtsService->validatePayment(
            $data['search'],
            $data['payment_intent_id']
        );

        return Response::success(
            ['validated_payment' => $validatedPayment],
            'Pago validado correctamente.'
        );
    }
    public function getStripePayments(GetStripePaymentsRequest $request)
    {
        $data = $request->validated();

        $payments = $this->debtsService->getPaymentsFromStripe(
            $data['search'],
            $data['year'] ?? null,
            $data['forceRefresh'] ?? false
        );

        return Response::success(
            ['payments' => $payments],
            empty($payments) ? 'No hay pagos registrados.' : null
        );
    }
}
