<?php

namespace App\Swagger\controllers\Payments\Students;

class Cards
{
/**
 * @OA\Delete(
 *     path="/api/v1/cards/{paymentMethodId}",
 *     tags={"Cards"},
 *     summary="Eliminar un método de pago",
 *     description="Elimina un método de pago específico asociado al usuario autenticado.",
 *     operationId="deleteUserCard",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *           name="X-User-Role",
 *           in="header",
 *           required=false,
 *           description="Rol requerido para este endpoint",
 *           @OA\Schema(
 *               type="string",
 *               example="student|parent"
 *           )
 *       ),
 *       @OA\Parameter(
 *           name="X-User-Permission",
 *           in="header",
 *           required=false,
 *           description="Permiso requerido para este endpoint",
 *           @OA\Schema(
 *                type="string",
 *                example="view.cards"
 *            )
 *       ),
 *     @OA\Parameter(
 *         name="paymentMethodId",
 *         in="path",
 *         description="ID del método de pago a eliminar (por ejemplo, 'pm_1P7E89AjcPzVqRkV')",
 *         required=true,
 *         @OA\Schema(type="string", example="pm_1P7E89AjcPzVqRkV")
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Método de pago eliminado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Método de pago eliminado correctamente"
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
 *
 * )
 */
public function deleteCard(){}


/**
 * @OA\Post(
 *     path="/api/v1/cards",
 *     tags={"Cards"},
 *     summary="Registrar un nuevo método de pago",
 *     description="Crea una sesión de Stripe Checkout para registrar una nueva tarjeta del usuario autenticado.",
 *     operationId="addUserCard",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *            name="X-User-Role",
 *            in="header",
 *            required=false,
 *            description="Rol requerido para este endpoint",
 *            @OA\Schema(
 *                type="string",
 *                example="student|parent"
 *            )
 *        ),
 *        @OA\Parameter(
 *            name="X-User-Permission",
 *            in="header",
 *            required=false,
 *            description="Permiso requerido para este endpoint",
 *            @OA\Schema(
 *                 type="string",
 *                 example="create.setup"
 *             )
 *        ),
 *     @OA\Response(
 *          response=201,
 *          description="Sesión de registro de método de pago creada exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="checkout_url",
 *                              type="string",
 *                              description="Url del checkout para agregar tarjeta",
 *                              example="https://checkout.stripe.com/c/pay/cs_test_xxx"
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
 *
 * )
 */
public function createCard(){}


/**
 * @OA\Get(
 *     path="/api/v1/cards/{studentId?}",
 *     tags={"Cards"},
 *     summary="Listar métodos de pago del usuario autenticado",
 *     description="Obtiene la lista de tarjetas o métodos de pago asociados al usuario autenticado. Permite forzar actualización del caché.",
 *     operationId="getUserCards",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *            name="X-User-Role",
 *            in="header",
 *            required=false,
 *            description="Rol requerido para este endpoint",
 *            @OA\Schema(
 *                type="string",
 *                example="student|parent"
 *            )
 *        ),
 *        @OA\Parameter(
 *            name="X-User-Permission",
 *            in="header",
 *            required=false,
 *            description="Permiso requerido para este endpoint",
 *            @OA\Schema(
 *                 type="string",
 *                 example="delete.card"
 *             )
 *        ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Si es true, fuerza la actualización del caché.",
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
 *          description="Lista de métodos de pago obtenida correctamente.",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="cards",
 *                              type="array",
 *                              description="Lista de métodos de pago del usuario",
 *                              @OA\Items(ref="#/components/schemas/DisplayPaymentMethodResponse")
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          nullable=true,
 *                          example="No se encontraron métodos de pago."
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
public function getCards(){}


}
