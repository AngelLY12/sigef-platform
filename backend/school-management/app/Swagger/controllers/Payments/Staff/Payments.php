<?php

namespace App\Swagger\controllers\Payments\Staff;

class Payments
{
/**
 * @OA\Get(
 *     path="/api/v1/payments",
 *     tags={"Payments"},
 *     summary="Obtener lista de pagos",
 *     description="Devuelve una lista paginada de pagos registrados, con opción de búsqueda por email, CURP o número de control, y posibilidad de forzar actualización del caché.",
 *     operationId="getPayments",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                           name="X-User-Role",
 *                           in="header",
 *                           required=false,
 *                           description="Rol requerido para este endpoint",
 *                           @OA\Schema(
 *                               type="string",
 *                               example="financial-staff"
 *                           )
 *                       ),
 *                       @OA\Parameter(
 *                           name="X-User-Permission",
 *                           in="header",
 *                           required=false,
 *                           description="Permiso requerido para este endpoint",
 *                           @OA\Schema(
 *                                type="string",
 *                                example="view.payments"
 *                            )
 *                       ),
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Filtro opcional para buscar por email, nombre o nombre de concepto de pago.",
 *         required=false,
 *         @OA\Schema(type="string", example="example@gmail.com")
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Número de resultados por página (por defecto 15).",
 *         required=false,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página actual (por defecto 1).",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *
 *     @OA\Response(
 *          response=200,
 *          description="Lista de pagos obtenida exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payments",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/PaymentListItemResponse")
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
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function payments(){}


/**
 * @OA\Get(
 *     path="/api/v1/payments/by-concept",
 *     tags={"Payments"},
 *     summary="Obtener lista de pagos por concepto",
 *     description="Devuelve una lista paginada de pagos registrados por concepto, con opción de búsqueda por email, CURP o número de control, y posibilidad de forzar actualización del caché.",
 *     operationId="getPaymentsByConcept",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                            name="X-User-Role",
 *                            in="header",
 *                            required=false,
 *                            description="Rol requerido para este endpoint",
 *                            @OA\Schema(
 *                                type="string",
 *                                example="financial-staff"
 *                            )
 *                        ),
 *                        @OA\Parameter(
 *                            name="X-User-Permission",
 *                            in="header",
 *                            required=false,
 *                            description="Permiso requerido para este endpoint",
 *                            @OA\Schema(
 *                                 type="string",
 *                                 example="view.payments"
 *                             )
 *                        ),
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Filtro opcional para buscar por email, nombre o nombre de concepto de pago.",
 *         required=false,
 *         @OA\Schema(type="string", example="example@gmail.com")
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Número de resultados por página (por defecto 15).",
 *         required=false,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página actual (por defecto 1).",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *
 *     @OA\Response(
 *          response=200,
 *          description="Lista de pagos obtenida exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payments",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/PaymentsMadeByConceptName")
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
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function paymentsByName(){}

/**
 * @OA\Get(
 *     path="/api/v1/payments/students",
 *     tags={"Payments"},
 *     summary="Obtener lista de alumnos",
 *     description="Devuelve una lista paginada de estudiantes registrados, con opción de búsqueda por email, CURP o número de control, y posibilidad de forzar actualización del caché.",
 *     operationId="getStudents",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                               name="X-User-Role",
 *                               in="header",
 *                               required=false,
 *                               description="Rol requerido para este endpoint",
 *                               @OA\Schema(
 *                                   type="string",
 *                                   example="financial-staff"
 *                               )
 *                           ),
 *                           @OA\Parameter(
 *                               name="X-User-Permission",
 *                               in="header",
 *                               required=false,
 *                               description="Permiso requerido para este endpoint",
 *                               @OA\Schema(
 *                                    type="string",
 *                                    example="view.payments.student.summary"
 *                                )
 *                           ),
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Filtro opcional para buscar por email, CURP o número de control del estudiante.",
 *         required=false,
 *         @OA\Schema(type="string", example="PERA020804MSHPNXA8")
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Número de resultados por página (por defecto 15).",
 *         required=false,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página actual (por defecto 1).",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización de caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *
 *     @OA\Response(
 *          response=200,
 *          description="Lista de estudiantes obtenida exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="payments",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/UserWithPendingSumamaryResponse")
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
public function students(){}

}
