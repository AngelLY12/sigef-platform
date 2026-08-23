<?php

namespace App\Http\Controllers\Admin;

use App\Core\Application\Services\Facades\Admin\ReconcileEventsServiceFacades;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\ReconciliationEventIndexRequest;
use App\Http\Requests\General\ForceRefreshRequest;
use Illuminate\Support\Facades\Response;

class AdminReconciliationEventsController extends Controller
{
    protected ReconcileEventsServiceFacades $facades;
    public function __construct(ReconcileEventsServiceFacades $facades)
    {
        $this->facades = $facades;
    }

    public function index(ReconciliationEventIndexRequest $request)
    {
        $filters = $request->toFilters();
        $forceRefresh = $request->boolean('forceRefresh',false);
        $events = $this->facades->getAllReconciliationEvents($filters, $forceRefresh);
        return Response::success(['reconcile_events' => $events], "Eventos de reconciliacion recuperados");

    }

    public function timeline(int $paymentId, ForceRefreshRequest $request)
    {
        $forceRefresh = $request->boolean('forceRefresh',false);
        $events = $this->facades->getReconcileEventsTimeline($paymentId, $forceRefresh);
        return Response::success(['reconcile_events_timeline' => $events], "Historial de eventos de reconciliacion recuperados");
    }

    public function show(int $eventId, ForceRefreshRequest $request)
    {
        $forceRefresh = $request->boolean('forceRefresh',false);
        $event = $this->facades->getReconcilitionEventById(id:$eventId, forceRefresh: $forceRefresh);
        return Response::success(['reconcile_event' => $event], "Evento de reconciliacion recuperado");
    }

}
