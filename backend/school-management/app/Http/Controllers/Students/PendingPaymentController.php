<?php

namespace App\Http\Controllers\Students;

use App\Core\Application\Services\Payments\Student\PendingPaymentServiceFacades;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Http\Controllers\Concerns\ResolvesRequestUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\General\ForceRefreshRequest;
use App\Http\Requests\Payments\Students\PayConceptRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Pending payment",
 *     description="Endpoints relacionados con el pago de conceptos pendientes y visualización"
 * )
 */
class PendingPaymentController extends Controller
{

    use ResolvesRequestUser;
    protected PendingPaymentServiceFacades $pendingPaymentService;

    public function __construct(PendingPaymentServiceFacades $pendingPaymentService)
    {
        $this->pendingPaymentService= $pendingPaymentService;

    }

    public function index(ForceRefreshRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;

        $pending=$this->pendingPaymentService->showPendingPayments(UserMapper::toDomain($user), $forceRefresh);
         return Response::success(
            ['pending_payments' => $pending],
            empty($pending) ? 'No hay pagos pendientes para el usuario.' : null
        );

    }

    public function overdue(ForceRefreshRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;

        $pending=$this->pendingPaymentService->showOverduePayments(UserMapper::toDomain($user), $forceRefresh);
        return Response::success(
            ['overdue_payments' => $pending],
            empty($pending) ? 'No hay pagos vencidos para el usuario.' : null
        );

    }

    public function store(PayConceptRequest $request)
    {
        $user = $this->targetUser($request);
        $payment= $this->pendingPaymentService->payConcept(
            UserMapper::toDomain($user),
            $request->validated()['concept_id']
        );
        return Response::success(
            ['url_checkout' => $payment],
            'El intento de pago se generó con éxito.',
            201
        );
    }
}
