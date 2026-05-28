<?php

namespace App\Swagger\controllers\Payments\Staff;

class Debts
{
/**
 * @OA\Get(
 *     path="/api/v1/debts/stripe-payments",
 *     summary="Obtener pagos desde Stripe",
 *     description="Obtiene todos los pagos registrados en Stripe asociados a un estudiante específico. Se puede filtrar por año y forzar actualización del caché.",
 *     tags={"Debts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                      name="X-User-Role",
 *                      in="header",
 *                      required=false,
 *                      description="Rol requerido para este endpoint (solo para documentación, ej. 'admin')",
 *                      @OA\Schema(
 *                          type="string",
 *                          example="financial-staff"
 *                      )
 *                  ),
 *                  @OA\Parameter(
 *                      name="X-User-Permission",
 *                      in="header",
 *                      required=false,
 *                      description="Permiso requerido para este endpoint",
 *                      @OA\Schema(
 *                           type="string",
 *                           example="view.stripe.payments"
 *                       )
 *                  ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Email, CURP o n_control",
 *         required=true,
 *         @OA\Schema(type="string", example="25687290")
 *     ),
 *     @OA\Parameter(
 *         name="year",
 *         in="query",
 *         description="Año específico de los pagos",
 *         required=false,
 *         @OA\Schema(type="integer", example=2025)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Si es true, fuerza la actualización del caché",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Pagos obtenidos correctamente desde Stripe",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payments",
 *                              type="array",
 *                              @OA\Items(ref="#/components/schemas/StripePaymentsResponse")
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=502, description="Error de Stripe", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function stripe(){}


/**
 * @OA\Post(
 *     path="/api/v1/debts/validate",
 *     summary="Validar un pago de Stripe",
 *     description="Valida un pago realizado en Stripe mediante el `payment_intent_id` y la búsqueda del estudiante.",
 *     tags={"Debts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                       name="X-User-Role",
 *                       in="header",
 *                       required=false,
 *                       description="Rol requerido para este endpoint",
 *                       @OA\Schema(
 *                           type="string",
 *                           example="financial-staff"
 *                       )
 *                   ),
 *                   @OA\Parameter(
 *                       name="X-User-Permission",
 *                       in="header",
 *                       required=false,
 *                       description="Permiso requerido para este endpoint",
 *                       @OA\Schema(
 *                            type="string",
 *                            example="validate.debt"
 *                        )
 *                   ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *            ref="#/components/schemas/ValidatePaymentRequest"
 *         )
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Pago validado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="validated_payment",
 *                              ref="#/components/schemas/PaymentValidateResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=502, description="Error de Stripe", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 */
public function validate(){}


/**
 * @OA\Get(
 *     path="/api/v1/debts",
 *     summary="Listar pagos pendientes",
 *     description="Obtiene una lista paginada de todos los pagos pendientes registrados. Permite buscar por nombre o control del estudiante, paginar los resultados y forzar la actualización del caché.",
 *     tags={"Debts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                        name="X-User-Role",
 *                        in="header",
 *                        required=false,
 *                        description="Rol requerido para este endpoint",
 *                        @OA\Schema(
 *                            type="string",
 *                            example="financial-staff"
 *                        )
 *                    ),
 *                    @OA\Parameter(
 *                        name="X-User-Permission",
 *                        in="header",
 *                        required=false,
 *                        description="Permiso requerido para este endpoint",
 *                        @OA\Schema(
 *                             type="string",
 *                             example="view.debts"
 *                         )
 *                    ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Texto de búsqueda para filtrar por CURP, email o número de control del estudiante.",
 *         required=false,
 *         @OA\Schema(type="string", example="example@gmail.com")
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Número de elementos por página",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página actual",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Si es true, fuerza la actualización del caché",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Lista de pagos pendientes obtenida correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="pending_payments",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/ConceptNameAndAmountResponse")
 *                                      )
 *                                  )
 *                              }
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function index(){}

}

