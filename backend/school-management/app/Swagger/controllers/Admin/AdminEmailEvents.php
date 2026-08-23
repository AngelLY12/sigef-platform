<?php

namespace App\Swagger\controllers\Admin;

class AdminEmailEvents
{

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/email-events",
     *     summary="Mostrar eventos de correo",
     *     description="Permite al administrador y supervisor consultar los eventos de correo registrados mediante filtros opcionales y paginación.",
     *     operationId="getAllEmailEvents",
     *     tags={"Admin - Email Events"},
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
     *         @OA\Schema(type="string", example="view.email-events")
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
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="query",
     *         required=false,
     *         description="Filtra por usuario.",
     *         @OA\Schema(type="integer", minimum=1, example=15)
     *     ),
     *     @OA\Parameter(
     *         name="eventType",
     *         in="query",
     *         required=false,
     *         description="Filtra por tipo de evento de correo.",
     *         @OA\Schema(ref="#/components/schemas/EmailEventType")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filtra por estado del evento.",
     *         @OA\Schema(ref="#/components/schemas/EmailEventStatus")
     *     ),
     *     @OA\Parameter(
     *         name="recipientEmail",
     *         in="query",
     *         required=false,
     *         description="Correo electrónico del destinatario.",
     *         @OA\Schema(type="string", format="email", example="alumno@example.com")
     *     ),
     *     @OA\Parameter(
     *         name="sourceType",
     *         in="query",
     *         required=false,
     *         description="Tipo de recurso que originó el evento.",
     *         @OA\Schema(ref="#/components/schemas/EmailEventSourceType")
     *     ),
     *     @OA\Parameter(
     *         name="sourceId",
     *         in="query",
     *         required=false,
     *         description="Identificador del recurso que originó el evento.",
     *         @OA\Schema(type="string", maxLength=100, example="concept_123")
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
     *         description="Eventos de correo obtenidos correctamente.",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(
     *                             property="email_events",
     *                             allOf={
     *                                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                                 @OA\Schema(
     *                                     @OA\Property(
     *                                         property="items",
     *                                         type="array",
     *                                         @OA\Items(ref="#/components/schemas/EmailEventResponse")
     *                                     )
     *                                 )
     *                             }
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="Eventos de correo recuperados"
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
     *     path="/api/v1/admin-actions/email-events/history/{userId}",
     *     summary="Mostrar timeline de eventos de correo de un usuario",
     *     description="Permite consultar cronológicamente los eventos de correo asociados a un usuario específico.",
     *     operationId="getUserEmailEventsTimeline",
     *     tags={"Admin - Email Events"},
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
     *         @OA\Schema(type="string", example="view.email-events")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID del usuario.",
     *         @OA\Schema(type="integer", minimum=1, example=15)
     *     ),
     *     @OA\Parameter(
     *         name="forceRefresh",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          required=false,
     *          @OA\Schema(type="integer", minimum=1, default=1)
     *      ),
     *      @OA\Parameter(
     *          name="perPage",
     *          in="query",
     *          required=false,
     *          @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *      ),
     *
     *      @OA\Parameter(
     *          name="eventType",
     *          in="query",
     *          required=false,
     *          description="Filtra por tipo de evento de correo.",
     *          @OA\Schema(ref="#/components/schemas/EmailEventType")
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          required=false,
     *          description="Filtra por estado del evento.",
     *          @OA\Schema(ref="#/components/schemas/EmailEventStatus")
     *      ),
     *
     *      @OA\Parameter(
     *          name="sourceType",
     *          in="query",
     *          required=false,
     *          description="Tipo de recurso que originó el evento.",
     *          @OA\Schema(ref="#/components/schemas/EmailEventSourceType")
     *      ),
     *
     *      @OA\Parameter(
     *          name="from",
     *          in="query",
     *          required=false,
     *          @OA\Schema(type="string", format="date-time", example="2026-08-01 00:00:00")
     *      ),
     *      @OA\Parameter(
     *          name="to",
     *          in="query",
     *          required=false,
     *          @OA\Schema(type="string", format="date-time", example="2026-08-16 23:59:59")
     *      ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Timeline de eventos de correo obtenido correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="email_events_history",
     *                              allOf={
     *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="items",
     *                                          type="array",
     *                                          @OA\Items(ref="#/components/schemas/EmailEventResponse")
     *                                      )
     *                                  )
     *                              }
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Historial de eventos de correo recuperados"
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *
     *
     *     @OA\Response(response=401, description="No autorizado.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Error interno del servidor.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function timeline(){}

}
