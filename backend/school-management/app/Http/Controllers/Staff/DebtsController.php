<?php

namespace App\Http\Controllers\Staff;

use App\Core\Application\Services\Facades\Payments\Staff\DebtsServiceFacades;
use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\Staff\GetStripePaymentsRequest;
use App\Http\Requests\Payments\Staff\PaginationWithSearchRequest;
use App\Http\Requests\Payments\Staff\ReconcilePaymentRequest;
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
   public function reconcilePayment(ReconcilePaymentRequest $request)
    {
        $data = $request->validated();
        $reconciled = $this->debtsService->reconcilePayment(
            $data['user_id'],
            $data['payment_id']
        );
        if($reconciled->reconciled === false)
        {
            return Response::error(message: $reconciled->message, status: 400, code: ErrorCode::BAD_REQUEST_RECONCILIATION->value);
        }
        return Response::success(
            ['reconciled_payment' => $reconciled],
            'Pago reconciliado correctamente.'
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
