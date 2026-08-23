<?php

namespace App\Http\Controllers\Admin;

use App\Core\Application\Services\Facades\Admin\PaymentEventsServiceFacades;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\PaymentEventIndexRequest;
use App\Http\Requests\General\ForceRefreshRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints para gestión administrativa (visualizacion de eventos de pago)"
 * )
 */
class AdminPaymentEventsController extends Controller
{
    protected PaymentEventsServiceFacades $facades;

    public function __construct(PaymentEventsServiceFacades $facades)
    {
        $this->facades = $facades;
    }

    public function index(PaymentEventIndexRequest $request)
    {
        $filters = $request->toFilters();
        $forceRefresh = $request->boolean('forceRefresh', false);
        $events = $this->facades->getAllPaymentEvents($filters, $forceRefresh);
        return Response::success(["payment_events" => $events], "Eventos de pago recuperados correctamente");
    }

    public function timeline(int $paymentId, ForceRefreshRequest $request)
    {
        $forceRefresh = $request->boolean('forceRefresh', false);
        $timeline = $this->facades->getPaymentEventsTimeline($paymentId, $forceRefresh);
        return Response::success(["payment_events_timeline" => $timeline], "Historial de eventos de pago recuperados correctamente");

    }

    public function show(int $eventId, ForceRefreshRequest $request)
    {
        $forceRefresh = $request->boolean('forceRefresh', false);
        $paymentEvent = $this->facades->getPaymentEventById($eventId, $forceRefresh);
        return Response::success(["payment_event" => $paymentEvent], "Evento de pago solicitado");
    }

}
