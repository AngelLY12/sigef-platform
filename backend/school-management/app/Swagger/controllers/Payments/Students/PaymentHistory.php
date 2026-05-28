<?php

namespace App\Swagger\controllers\Payments\Students;

class PaymentHistory
{

/**
 * @OA\Get(
 *     path="/api/v1/payments/history/{studentId?}",
 *     tags={"Payment History"},
 *     summary="Obtener historial de pagos del usuario autenticado",
 *     description="Devuelve el historial de pagos del usuario logueado, con soporte para paginación y cacheo.",
 *     operationId="getUserPaymentHistory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="student|parent"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="view.payments.history"
 *                           )
 *                      ),
 *
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Cantidad de registros por página (por defecto 15).",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página (por defecto 1).",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
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
     *     path="/api/v1/payments/history/payment/{id}",
     *     summary="Buscar pago por ID",
     *     description="Obtiene la información detallada de un pago específico mediante su identificador.",
     *     tags={"Payment History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *                          name="X-User-Role",
     *                          in="header",
     *                          required=false,
     *                          description="Rol requerido para este endpoint",
     *                          @OA\Schema(
     *                              type="string",
     *                              example="student|parent"
     *                          )
     *                      ),
     *                      @OA\Parameter(
     *                          name="X-User-Permission",
     *                          in="header",
     *                          required=false,
     *                          description="Permiso requerido para este endpoint",
     *                          @OA\Schema(
     *                               type="string",
     *                               example="view.payments.history"
     *                           )
     *                      ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del pago a buscar",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Pago encontrado correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="payment",
     *                              ref="#/components/schemas/PaymentToDisplay"
     *                          )
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getPayment(){}


    /**
     * @OA\Get(
     *     path="/api/v1/payments/history/receipt/{paymentId}",
     *     summary="Obtener URL del recibo de pago",
     *     description="Genera y devuelve una URL temporal firmada para visualizar o descargar el recibo de un pago específico en formato HTML.",
     *     tags={"Receipts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-User-Role",
     *         in="header",
     *         required=false,
     *         description="Rol requerido para este endpoint",
     *         @OA\Schema(
     *             type="string",
     *             example="student|parent"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-User-Permission",
     *         in="header",
     *         required=false,
     *         description="Permiso requerido para este endpoint",
     *         @OA\Schema(
     *             type="string",
     *             example="view.receipt"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         required=true,
     *         description="ID del pago para generar el recibo",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URL del recibo generada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="url",
     *                     type="string",
     *                     format="uri",
     *                     example="https://storage.googleapis.com/bucket-name/receipts/2024/02/REC-123.html?X-Goog-Algorithm=...&X-Goog-Expires=300",
     *                     description="URL temporal firmada con validez de 5 minutos para visualizar o descargar el recibo"
     *                 ),
     *                 @OA\Property(
     *                     property="expires_in",
     *                     type="integer",
     *                     example=300,
     *                     description="Tiempo de expiración en segundos"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="content_type",
     *                     type="string",
     *                     example="text/html",
     *                     description="Tipo de contenido del archivo"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Recibo generado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pago no encontrado o recibo no disponible",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Recibo no encontrado"),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado - Permiso 'view.receipt' requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized - Missing permission: view.receipt"),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno al generar el recibo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al generar el recibo"),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     )
     * )
     */
    public function getReceipt(){}

}
