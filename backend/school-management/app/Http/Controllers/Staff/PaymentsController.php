<?php

namespace App\Http\Controllers\Staff;

use App\Core\Application\Services\Payments\Staff\PaymentsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\Staff\PaginationWithSearchRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Endpoints para la gestión y consulta de pagos registrados"
 * )
 */
class PaymentsController extends Controller
{

    protected PaymentsService $paymentsService;

    public function __construct(PaymentsService $paymentsService)
    {
        $this->paymentsService = $paymentsService;
    }

    public function index(PaginationWithSearchRequest $request)
    {
        $search = $request->validated()['search'] ?? null;
        $perPage = $request->validated()['perPage'] ?? 15;
        $page = $request->validated()['page'] ?? 1;
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $payments = $this->paymentsService->showAllPayments($search, $perPage, $page, $forceRefresh);

        return Response::success(
            ['payments' => $payments],
            empty($payments->items) ? 'No hay pagos registrados.' : null
        );
    }

    public function showByName(PaginationWithSearchRequest $request)
    {
        $search = $request->validated()['search'] ?? null;
        $perPage = $request->validated()['perPage'] ?? 15;
        $page = $request->validated()['page'] ?? 1;
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $payments= $this->paymentsService->showAllPaymentsByConceptName($search, $perPage, $page, $forceRefresh);
        return Response::success(['payments' => $payments],
            empty($payments->items) ? 'No hay pagos registrados.' : null);
    }

    public function showByStudents(PaginationWithSearchRequest $request)
    {
        $search = $request->validated()['search'] ?? null;
        $perPage = $request->validated()['perPage'] ?? 15;
        $page = $request->validated()['page'] ?? 1;
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $students = $this->paymentsService->showAllStudents($search, $perPage, $page, $forceRefresh);

        return Response::success(
            ['students' => $students],
            empty($students->items) ? 'No hay estudiantes registrados.' : null
        );
    }

}
