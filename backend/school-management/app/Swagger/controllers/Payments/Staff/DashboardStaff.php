<?php

namespace App\Swagger\controllers\Payments\Staff;

class DashboardStaff
{
/**
 * @OA\Post(
 *     path="/api/v1/dashboard-staff/refresh",
 *     summary="Limpiar el caché del dashboard",
 *     description="Forza el borrado del caché en todos los datos del dashboard.",
 *     tags={"Dashboard Staff"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *           name="X-User-Role",
 *           in="header",
 *           required=false,
 *           description="Rol requerido para este endpoint",
 *           @OA\Schema(
 *               type="string",
 *               example="financial-staff"
 *           )
 *       ),
 *       @OA\Parameter(
 *           name="X-User-Permission",
 *           in="header",
 *           required=false,
 *           description="Permiso requerido para este endpoint",
 *           @OA\Schema(
 *                type="string",
 *                example="refresh.all.dashboard"
 *            )
 *       ),
 *     @OA\Response(
 *          response=200,
 *          description="Caché limpiado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Dashboard cache limpiado con éxito"
 *                      )
 *                  )
 *              }
 *          )
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
 *     path="/api/v1/dashboard-staff/concepts",
 *     summary="Obtener todos los conceptos de pago",
 *     description="Devuelve una lista paginada de conceptos de pago visibles en el panel del personal. Permite filtrar por año actual y forzar actualización del caché.",
 *     tags={"Dashboard Staff"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *            name="X-User-Role",
 *            in="header",
 *            required=false,
 *            description="Rol requerido para este endpoint",
 *            @OA\Schema(
 *                type="string",
 *                example="financial-staff"
 *            )
 *        ),
 *        @OA\Parameter(
 *            name="X-User-Permission",
 *            in="header",
 *            required=false,
 *            description="Permiso requerido para este endpoint",
 *            @OA\Schema(
 *                 type="string",
 *                 example="view.concepts.summary"
 *             )
 *        ),
 *     @OA\Parameter(
 *         name="only_this_year",
 *         in="query",
 *         description="Si es true, filtra los conceptos al año actual",
 *         required=false,
 *         @OA\Schema(type="boolean", example=true)
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Cantidad de registros por página",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página a obtener",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Si es true, fuerza actualización del caché",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Lista de conceptos obtenida correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concepts",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/ConceptsToDashboardResponse")
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
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function concepts(){}


/**
 * @OA\Get(
 *     path="/api/v1/dashboard-staff/payments",
 *     summary="Obtener monto total de pagos realizados",
 *     tags={"Dashboard Staff"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *             name="X-User-Role",
 *             in="header",
 *             required=false,
 *             description="Rol requerido para este endpoint",
 *             @OA\Schema(
 *                 type="string",
 *                 example="financial-staff"
 *             )
 *         ),
 *         @OA\Parameter(
 *             name="X-User-Permission",
 *             in="header",
 *             required=false,
 *             description="Permiso requerido para este endpoint",
 *             @OA\Schema(
 *                  type="string",
 *                  example="view.all.paid.concepts.summary"
 *              )
 *         ),
 *     @OA\Parameter(
 *         name="only_this_year",
 *         in="query",
 *         description="Filtrar solo por el año actual",
 *         @OA\Schema(type="boolean", example=true)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché",
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Monto total de pagos realizados obtenido correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payments_data",
 *                              ref="#/components/schemas/FinancialSummaryResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function payments(){}


/**
 * @OA\Get(
 *     path="/api/v1/dashboard-staff/students",
 *     summary="Obtener el número total de estudiantes",
 *     tags={"Dashboard Staff"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *              name="X-User-Role",
 *              in="header",
 *              required=false,
 *              description="Rol requerido para este endpoint",
 *              @OA\Schema(
 *                  type="string",
 *                  example="financial-staff"
 *              )
 *          ),
 *          @OA\Parameter(
 *              name="X-User-Permission",
 *              in="header",
 *              required=false,
 *              description="Permiso requerido para este endpoint",
 *              @OA\Schema(
 *                   type="string",
 *                   example="view.all.students.summary"
 *               )
 *          ),
 *     @OA\Parameter(
 *         name="only_this_year",
 *         in="query",
 *         description="Filtrar solo por el año actual",
 *         @OA\Schema(type="boolean", example=true)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché",
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Número total de estudiantes obtenido correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                       @OA\Property(
 *                           property="data",
 *                           type="object",
 *                           @OA\Property(
 *                               property="payments_data",
 *                               ref="#/components/schemas/UsersFinancialSummary"
 *                           )
 *                       )
 *                   )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function students(){}


/**
 * @OA\Get(
 *     path="/api/v1/dashboard-staff/pending",
 *     summary="Obtener cantidad y monto total de pagos pendientes",
 *     description="Devuelve el total de conceptos pendientes de pago, incluyendo cantidad y monto total. Se puede filtrar por el año actual y forzar la actualización del caché.",
 *     tags={"Dashboard Staff"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *               name="X-User-Role",
 *               in="header",
 *               required=false,
 *               description="Rol requerido para este endpoint",
 *               @OA\Schema(
 *                   type="string",
 *                   example="financial-staff"
 *               )
 *           ),
 *           @OA\Parameter(
 *               name="X-User-Permission",
 *               in="header",
 *               required=false,
 *               description="Permiso requerido para este endpoint",
 *               @OA\Schema(
 *                    type="string",
 *                    example="view.all.pending.concepts.summary"
 *                )
 *           ),
 *     @OA\Parameter(
 *         name="only_this_year",
 *         in="query",
 *         description="Filtrar solo por el año actual",
 *         required=false,
 *         @OA\Schema(type="boolean", example=true)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
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
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function pending(){}

/**
 * @OA\Post(
 *     path="/api/v1/dashboard-staff/payout",
 *     summary="Crear un payout con todo el balance disponible",
 *     description="Crea un payout en Stripe transfiriendo TODO el balance disponible en MXN a la cuenta bancaria registrada. Requiere un mínimo de $100.00 MXN disponibles.",
 *     tags={"Dashboard Staff"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *                name="X-User-Role",
 *                in="header",
 *                required=false,
 *                description="Rol requerido para este endpoint",
 *                @OA\Schema(
 *                    type="string",
 *                    example="financial-staff"
 *                )
 *            ),
 *            @OA\Parameter(
 *                name="X-User-Permission",
 *                in="header",
 *                required=false,
 *                description="Permiso requerido para este endpoint",
 *                @OA\Schema(
 *                     type="string",
 *                     example="create.payout"
 *                 )
 *            ),
 *     @OA\Response(
 *          response=200,
 *          description="Payout creado exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payout",
 *                              ref="#/components/schemas/StripePayoutResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=400, description="Solicitud incorrecta", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=502, description="Error de Stripe", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *
 * )
 */
public function payouts()
{}


}
