<?php

namespace App\Swagger\controllers\Payments\Students;

class PendingPayment
{
/**
 * @OA\Get(
 *     path="/api/v1/pending-payments/{studentId?}",
 *     tags={"Pending Payment"},
 *     summary="Obtener pagos pendientes del usuario autenticado",
 *     description="Devuelve todos los conceptos pendientes de pago del usuario logueado.",
 *     operationId="getUserPendingPayments",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                            name="X-User-Role",
 *                            in="header",
 *                            required=false,
 *                            description="Rol requerido para este endpoint",
 *                            @OA\Schema(
 *                                type="string",
 *                                example="student|parent"
 *                            )
 *                        ),
 *                        @OA\Parameter(
 *                            name="X-User-Permission",
 *                            in="header",
 *                            required=false,
 *                            description="Permiso requerido para este endpoint",
 *                            @OA\Schema(
 *                                 type="string",
 *                                 example="view.pending.concepts"
 *                             )
 *                        ),
 *
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del children, (opcional)",
 *         required=false,
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Pagos pendientes obtenidos correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="pending_payments",
 *                              type="array",
 *                              @OA\Items(ref="#/components/schemas/PendingPaymentConceptsResponse")
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
public function pending(){}


/**
 * @OA\Post(
 *     path="/api/v1/pending-payments",
 *     tags={"Pending Payment"},
 *     summary="Generar intento de pago para un concepto pendiente",
 *     description="Crea un intento de pago en Stripe (u otro proveedor) para el concepto indicado y devuelve la URL del checkout.",
 *     operationId="createPaymentIntent",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                             name="X-User-Role",
 *                             in="header",
 *                             required=false,
 *                             description="Rol requerido para este endpoint",
 *                             @OA\Schema(
 *                                 type="string",
 *                                 example="student|parent"
 *                             )
 *                         ),
 *                         @OA\Parameter(
 *                             name="X-User-Permission",
 *                             in="header",
 *                             required=false,
 *                             description="Permiso requerido para este endpoint",
 *                             @OA\Schema(
 *                                  type="string",
 *                                  example="create.payment"
 *                              )
 *                         ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos necesarios para generar el intento de pago",
 *         @OA\JsonContent(
 *            ref="#/components/schemas/PayConceptRequest"
 *         )
 *     ),
 *
 *     @OA\Response(
 *          response=201,
 *          description="Intento de pago generado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="url_checkout",
 *                              type="string",
 *                              example="https://checkout.stripe.com/pay/cs_test_a1b2c3d4e5"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=502, description="Error de Stripe", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function payConcept(){}


/**
 * @OA\Get(
 *     path="/api/v1/pending-payments/overdue/{studentId?}",
 *     summary="Obtener pagos vencidos del usuario autenticado",
 *     description="Devuelve los pagos que ya están vencidos para el usuario autenticado.",
 *     operationId="getUserOverduePayments",
 *     tags={"Pending Payment"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                              name="X-User-Role",
 *                              in="header",
 *                              required=false,
 *                              description="Rol requerido para este endpoint",
 *                              @OA\Schema(
 *                                  type="string",
 *                                  example="student|parent"
 *                              )
 *                          ),
 *                          @OA\Parameter(
 *                              name="X-User-Permission",
 *                              in="header",
 *                              required=false,
 *                              description="Permiso requerido para este endpoint",
 *                              @OA\Schema(
 *                                   type="string",
 *                                   example="view.overdue.concepts"
 *                               )
 *                          ),
 *
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché (true/false)",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del children, (opcional)",
 *         required=false,
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Pagos vencidos obtenidos correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="pending_payments",
 *                              type="array",
 *                              @OA\Items(ref="#/components/schemas/PendingPaymentConceptsResponse")
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
public function overdue(){}

}
