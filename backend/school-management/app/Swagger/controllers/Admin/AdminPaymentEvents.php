<?php

namespace App\Swagger\controllers\Admin;

class AdminPaymentEvents
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/payment-events",
     *     summary="Mostrar eventos de pago",
     *     description="Permite al administrador y supervisor consultar los eventos de pago registrados, utilizando filtros opcionales y paginación.",
     *     operationId="getAllPaymentEvents",
     *     tags={"Admin - Payment Events"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="X-User-Role",
     *         in="header",
     *         required=false,
     *         description="Rol requerido para este endpoint.",
     *         @OA\Schema(
     *             type="string",
     *             example="admin|supervisor"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-User-Permission",
     *         in="header",
     *         required=false,
     *         description="Permiso requerido para este endpoint.",
     *         @OA\Schema(
     *             type="string",
     *             example="view.payment-events"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="forceRefresh",
     *         in="query",
     *         required=false,
     *         description="Fuerza la actualización del caché.",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Número de página.",
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Cantidad de registros por página.",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="query",
     *         required=false,
     *         description="Filtra los eventos asociados a un pago específico.",
     *         @OA\Schema(type="integer", minimum=1, example=125)
     *     ),
     *     @OA\Parameter(
     *         name="eventType",
     *         in="query",
     *         required=false,
     *         description="Filtra por tipo de evento de pago.",
     *         @OA\Schema(ref="#/components/schemas/PaymentEventType")
     *     ),
     *     @OA\Parameter(
     *         name="processed",
     *         in="query",
     *         required=false,
     *         description="Filtra por eventos procesados o no procesados.",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="stripePaymentIntentId",
     *         in="query",
     *         required=false,
     *         description="Identificador del PaymentIntent de Stripe.",
     *         @OA\Schema(type="string", maxLength=100, example="pi_3Nxxxxxxxxxxxx")
     *     ),
     *     @OA\Parameter(
     *         name="stripeSessionId",
     *         in="query",
     *         required=false,
     *         description="Identificador de la sesión de Checkout de Stripe.",
     *         @OA\Schema(type="string", maxLength=100, example="cs_test_xxxxxxxxxxxx")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=false,
     *         description="Fecha inicial del rango de búsqueda.",
     *         @OA\Schema(type="string", format="date-time", example="2026-08-01 00:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         description="Fecha final del rango de búsqueda.",
     *         @OA\Schema(type="string", format="date-time", example="2026-08-16 23:59:59")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Eventos de pago obtenidos correctamente.",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(
     *                             property="payment_events",
     *                             allOf={
     *                                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                                 @OA\Schema(
     *                                     @OA\Property(
     *                                         property="items",
     *                                         type="array",
     *                                         @OA\Items(ref="#/components/schemas/PaymentEventResponse")
     *                                     )
     *                                 )
     *                             }
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="Eventos de pago recuperados correctamente"
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Los filtros proporcionados no son válidos.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/payment-events/timeline/{paymentId}",
     *     summary="Mostrar timeline de eventos de un pago",
     *     description="Permite consultar cronológicamente los eventos asociados a un pago específico.",
     *     operationId="getPaymentEventsTimeline",
     *     tags={"Admin - Payment Events"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="X-User-Role",
     *         in="header",
     *         required=false,
     *         description="Rol requerido para este endpoint.",
     *         @OA\Schema(type="string", example="admin|supervisor")
     *     ),
     *     @OA\Parameter(
     *         name="X-User-Permission",
     *         in="header",
     *         required=false,
     *         description="Permiso requerido para este endpoint.",
     *         @OA\Schema(type="string", example="view.payment-events")
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
     *         description="Fuerza la actualización del caché.",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Timeline de eventos de pago obtenido correctamente.",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(
     *                             property="payment_events_timeline",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/PaymentEventResponse")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="Historial de eventos de pago recuperados correctamente"
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function timeline(){}

}
