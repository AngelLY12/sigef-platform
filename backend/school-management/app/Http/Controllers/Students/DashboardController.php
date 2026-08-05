<?php

namespace App\Http\Controllers\Students;

use App\Core\Application\Services\Payments\Student\DashboardServiceFacades;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Http\Controllers\Concerns\ResolvesRequestUser;
use App\Http\Requests\Payments\Staff\DashboardRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\General\PaginationRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Endpoints relacionados con el panel de control del usuario (estadísticas, pagos y resumen financiero)"
 * )
 */

class DashboardController extends Controller
{

    use ResolvesRequestUser;
    protected DashboardServiceFacades $dashboardService;

    public function __construct(DashboardServiceFacades $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function pending(DashboardRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $onlyThisYear = $request->validated()['only_this_year'] ?? false;

        $data = $this->dashboardService->pendingPaymentAmount($onlyThisYear,UserMapper::toDomain($user), $forceRefresh);

        return Response::success(['total_pending' => $data]);

    }



    public function paid(DashboardRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $onlyThisYear = $request->validated()['only_this_year'] ?? false;

        $data = $this->dashboardService->paymentsMade($onlyThisYear,UserMapper::toDomain($user), $forceRefresh);

        return Response::success(['paid_data' => $data]);

    }


    public function overdue(DashboardRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $onlyThisYear = $request->validated()['only_this_year'] ?? false;

        $data = $this->dashboardService->overduePayments($onlyThisYear,UserMapper::toDomain($user), $forceRefresh);

        return Response::success(['total_overdue' => $data]);

    }

    public function history(PaginationRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $onlyThisYear = $request->validated()['only_this_year'] ?? false;

        $perPage = $request->integer('perPage', 15);
        $page = $request->integer('page', 1);
        $data = $this->dashboardService->paymentHistory($onlyThisYear,UserMapper::toDomain($user), $perPage, $page, $forceRefresh);

        return Response::success(
            ['payment_history' => $data],
            empty($data->items) ? 'No hay pagos registrados en el historial' : null
        );
    }


    public function refreshDashboard(Request $request)
    {
        $user = $this->targetUser($request);
        $this->dashboardService->refreshAll($user->id);
        return Response::success(null, 'Dashboard cache limpiado con éxito');

    }
}
