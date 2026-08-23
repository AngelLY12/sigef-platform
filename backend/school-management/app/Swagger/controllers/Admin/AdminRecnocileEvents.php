<?php

namespace App\Swagger\controllers\Admin;

class AdminRecnocileEvents
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/reconciliation-events",
     *     summary="Mostrar eventos de reconciliación",
     *     description="Permite al administrador y supervisor consultar los eventos de reconciliación registrados mediante filtros opcionales y paginación.",
     *     operationId="getAllReconciliationEvents",
     *     tags={"Admin - Reconciliation Events"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="X-User-Role",
     *         in="header",
     *         required=false,
     *         @OA\Schema(type="string", example="admin|supervisor")
     *     ),
     *     @OA\Parameter(
     *         name="X-User-Permission",
     *         in="header",
     *         required=false,
     *         @OA\Schema(type="string", example="view.reconciliation-events")
     *     ),
     *     @OA\Parameter(
     *         name="forceRefresh",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="query",
     *         required=false,
     *         description="Filtra por pago.",
     *         @OA\Schema(type="integer", minimum=1, example=125)
     *     ),
     *     @OA\Parameter(
     *         name="sourceType",
     *         in="query",
     *         required=false,
     *         description="Tipo de origen del evento.",
     *         @OA\Schema(ref="#/components/schemas/ReconciliationSourceType")
     *     ),
     *     @OA\Parameter(
     *         name="sourceId",
     *         in="query",
     *         required=false,
     *         description="Identificador del origen.",
     *         @OA\Schema(type="string", maxLength=100, example="stripe_sync_123")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Estado del evento de reconciliación.",
     *         @OA\Schema(ref="#/components/schemas/ReconciliationEventStatus")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2026-08-01 00:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2026-08-16 23:59:59")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Eventos de reconciliación obtenidos correctamente.",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(
     *                             property="reconcile_events",
     *                             allOf={
     *                                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                                 @OA\Schema(
     *                                     @OA\Property(
     *                                         property="items",
     *                                         type="array",
     *                                         @OA\Items(ref="#/components/schemas/ReconcileEventResponse")
     *                                     )
     *                                 )
     *                             }
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="Eventos de reconciliación recuperados"
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autorizado.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Los filtros proporcionados no son válidos.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Error interno del servidor.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/reconciliation-events/timeline/{paymentId}",
     *     summary="Mostrar timeline de eventos de reconciliación de un pago",
     *     description="Permite consultar cronológicamente los eventos de reconciliación asociados a un pago específico.",
     *     operationId="getPaymentReconciliationEventsTimeline",
     *     tags={"Admin - Reconciliation Events"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="X-User-Role",
     *         in="header",
     *         required=false,
     *         @OA\Schema(type="string", example="admin|supervisor")
     *     ),
     *     @OA\Parameter(
     *         name="X-User-Permission",
     *         in="header",
     *         required=false,
     *         @OA\Schema(type="string", example="view.reconciliation-events")
     *     ),
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         required=true,
     *         description="ID del pago.",
     *         @OA\Schema(type="integer", minimum=1, example=125)
     *     ),
     *     @OA\Parameter(
     *         name="forceRefresh",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Timeline de eventos de reconciliación obtenido correctamente.",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(
     *                             property="reconcile_events_timeline",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/ReconcileEventResponse")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="Historial de eventos de reconciliación recuperados"
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autorizado.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Error interno del servidor.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function timeline(){}

}
