<?php

namespace App\Http\Controllers\Admin;

use App\Core\Application\Services\Facades\Admin\EmailEventsServiceFacades;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\EmailEventHistoryRequest;
use App\Http\Requests\Admin\Events\EmailEventIndexRequest;
use App\Http\Requests\General\ForceRefreshRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints para gestión administrativa (visualizacion de eventos de correo)"
 * )
 */
class AdminEmailEventsController extends Controller
{
    protected EmailEventsServiceFacades $facades;
    public function __construct(EmailEventsServiceFacades $facades)
    {
        $this->facades = $facades;
    }

    public function index(EmailEventIndexRequest $request)
    {
        $filters = $request->toFilters();
        $forceRefresh = $request->boolean('forceRefresh', false);
        $events = $this->facades->getAllEmailEvents($filters, $forceRefresh);
        return Response::success(["email_events" => $events], "Eventos de correo recuperados");
    }

    public function history(int $userId, EmailEventHistoryRequest $request)
    {
        $filters = $request->toFilters();
        $forceRefresh = $request->boolean('forceRefresh', false);
        $events = $this->facades->getEmailEventsHistory($filters,$userId, $forceRefresh);
        return Response::success(["email_events_history" => $events], "Historial de eventos de correo recuperados");
    }

    public function show(int $eventId, ForceRefreshRequest $request)
    {
        $forceRefresh = $request->boolean('forceRefresh', false);
        $event = $this->facades->getEmailEventById($eventId, $forceRefresh);
        return Response::success(["email_event" => $event], "Evento de correo recuperado");
    }

}
