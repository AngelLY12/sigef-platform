<?php

namespace App\Swagger\controllers\Payments\Students;

class DashboardStudent
{
/**
 * @OA\Post(
 *     path="/api/v1/dashboard/refresh/{studentId?}",
 *     tags={"Dashboard Student"},
 *     summary="Limpiar caché del dashboard",
 *     description="Limpia el caché de datos almacenados en el dashboard (estadísticas, pagos, etc.)",
 *     operationId="refreshDashboardCache",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                 name="X-User-Role",
 *                 in="header",
 *                 required=false,
 *                 description="Rol requerido para este endpoint",
 *                 @OA\Schema(
 *                     type="string",
 *                     example="student|parent"
 *                 )
 *             ),
 *             @OA\Parameter(
 *                 name="X-User-Permission",
 *                 in="header",
 *                 required=false,
 *                 description="Permiso requerido para este endpoint",
 *                 @OA\Schema(
 *                      type="string",
 *                      example="refresh.all.dashboard"
 *                  )
 *             ),
 *     @OA\Parameter(
 *          name="id",
 *          in="path",
 *          description="ID del children (opcional)",
 *          required=false,
 *          @OA\Schema(type="integer", example=3)
 *      ),
 *     @OA\Response(
 *          response=200,
 *          description="Caché del dashboard limpiado con éxito",
 *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function refresh(){}


/**
 * @OA\Get(
 *     path="/api/v1/dashboard/history/{studentId?}",
 *     tags={"Dashboard Student"},
 *     summary="Obtener historial de pagos del usuario autenticado",
 *     description="Devuelve una lista paginada con el historial de pagos realizados por el usuario autenticado. Permite forzar la actualización del caché.",
 *     operationId="getPaymentHistory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                  name="X-User-Role",
 *                  in="header",
 *                  required=false,
 *                  description="Rol requerido para este endpoint",
 *                  @OA\Schema(
 *                      type="string",
 *                      example="student|parent"
 *                  )
 *              ),
 *              @OA\Parameter(
 *                  name="X-User-Permission",
 *                  in="header",
 *                  required=false,
 *                  description="Permiso requerido para este endpoint",
 *                  @OA\Schema(
 *                       type="string",
 *                       example="view.payments.summary"
 *                   )
 *              ),
 *
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Número de registros por página",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false)",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del children (opcional)",
 *         required=false,
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Historial de pagos obtenido correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payment_history",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/PaymentHistoryResponse")
 *                                      )
 *                                  )
 *                              }
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function history(){}


/**
 * @OA\Get(
 *     path="/api/v1/dashboard/overdue/{studentId?}",
 *     tags={"Dashboard Student"},
 *     summary="Obtener total de pagos vencidos del usuario",
 *     description="Devuelve el monto total de los pagos vencidos asociados al usuario autenticado.",
 *     operationId="getOverduePayments",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                   name="X-User-Role",
 *                   in="header",
 *                   required=false,
 *                   description="Rol requerido para este endpoint",
 *                   @OA\Schema(
 *                       type="string",
 *                       example="student|parent"
 *                   )
 *               ),
 *               @OA\Parameter(
 *                   name="X-User-Permission",
 *                   in="header",
 *                   required=false,
 *                   description="Permiso requerido para este endpoint",
 *                   @OA\Schema(
 *                        type="string",
 *                        example="view.own.overdue.concepts.summary"
 *                    )
 *               ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false)",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del children (opcional)",
 *         required=false,
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Cantidad de pagos vencidos obtenido correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                       @OA\Property(
 *                           property="data",
 *                           type="object",
 *                           @OA\Property(
 *                               property="total_overdue",
 *                               ref="#/components/schemas/PendingSummaryResponse"
 *                           )
 *                       )
 *                   )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function overdue(){}

/**
 * @OA\Get(
 *     path="/api/v1/dashboard/paid/{studentId?}",
 *     tags={"Dashboard Student"},
 *     summary="Obtener total de pagos realizados por el usuario",
 *     description="Devuelve el monto total de pagos completados por el usuario autenticado.",
 *     operationId="getPaidAmount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                    name="X-User-Role",
 *                    in="header",
 *                    required=false,
 *                    description="Rol requerido para este endpoint",
 *                    @OA\Schema(
 *                        type="string",
 *                        example="student|parent"
 *                    )
 *                ),
 *                @OA\Parameter(
 *                    name="X-User-Permission",
 *                    in="header",
 *                    required=false,
 *                    description="Permiso requerido para este endpoint",
 *                    @OA\Schema(
 *                         type="string",
 *                         example="view.own.paid.concepts.summary"
 *                     )
 *                ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false)",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del children (opcional)",
 *         required=false,
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Monto total de pagos realizados obtenido correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="paid_data",
 *                          ref="#/components/schemas/PaymentsSummaryResponse"
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function paid(){}


/**
 * @OA\Get(
 *     path="/api/v1/dashboard/pending/{studentId?}",
 *     tags={"Dashboard Student"},
 *     summary="Obtener total de pagos pendientes del usuario",
 *     description="Devuelve la cantidad y monto total de los pagos pendientes del usuario autenticado.",
 *     operationId="getPendingPayments",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                     name="X-User-Role",
 *                     in="header",
 *                     required=false,
 *                     description="Rol requerido para este endpoint",
 *                     @OA\Schema(
 *                         type="string",
 *                         example="student|parent"
 *                     )
 *                 ),
 *                 @OA\Parameter(
 *                     name="X-User-Permission",
 *                     in="header",
 *                     required=false,
 *                     description="Permiso requerido para este endpoint",
 *                     @OA\Schema(
 *                          type="string",
 *                          example="view.own.pending.concepts.summary"
 *                      )
 *                 ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false)",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del children (opcional)",
 *         required=false,
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *      @OA\Response(
 *          response=200,
 *          description="Totales de pagos pendientes obtenidos correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="total_pending",
 *                              ref="#/components/schemas/PendingSummaryResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function pending(){}


}

