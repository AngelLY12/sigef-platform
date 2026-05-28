<?php

namespace App\Swagger\controllers\Misc;

class Parents
{
/**
 *
 * @OA\Post(
 *     path="/api/v1/parents/invite",
 *     summary="Enviar invitación a un padre",
 *     tags={"Parents"},
 *     operationId="inviteParent",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                           name="X-User-Role",
 *                           in="header",
 *                           required=false,
 *                           description="Rol requerido para este endpoint",
 *                           @OA\Schema(
 *                               type="string",
 *                               example="student"
 *                           )
 *                       ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/SendInviteRequest")
 *     ),
 *     @OA\Response(
 *          response=201,
 *          description="Invitación enviada",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(property="token", type="string", example="uuid-token"),
 *                          @OA\Property(property="expires_at", type="string", format="date-time", example="2025-11-27T12:34:56Z")
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
public function invite(){}


/**
 *
 * @OA\Post(
 *     path="/api/v1/parents/invite/accept",
 *     summary="Aceptar invitación de un padre",
 *     tags={"Parents"},
 *     operationId="acceptInvitation",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                            name="X-User-Role",
 *                            in="header",
 *                            required=false,
 *                            description="Rol requerido para este endpoint",
 *                            @OA\Schema(
 *                                type="string",
 *                                example="parent"
 *                            )
 *                        ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/AcceptInviteRequest")
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Invitación aceptada",
 *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function accept(){}


/**
 *
 * @OA\Get(
 *     path="/api/v1/parents/get-children",
 *     summary="Obtiene los hijos del parent",
 *     tags={"Parents"},
 *     operationId="getParentChildren",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                            name="X-User-Role",
 *                            in="header",
 *                            required=false,
 *                            description="Rol requerido para este endpoint",
 *                            @OA\Schema(
 *                                type="string",
 *                                example="parent"
 *                            )
 *                        ),
 *
 *     @OA\Response(
 *          response=200,
 *          description="Hijos obtenidos correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="children",
 *                              type="array",
 *                              description="Lista de hijos del usuario",
 *                              @OA\Items(ref="#/components/schemas/ParentChildrenResponse")
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
 *
 * )
 */
public function getChildren(){}
/**
 *
 * @OA\Get(
 *     path="/api/parents/get-parents",
 *     summary="Obtiene los familiares del hijo",
 *     tags={"Parents"},
 *     operationId="getStudentParents",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                            name="X-User-Role",
 *                            in="header",
 *                            required=false,
 *                            description="Rol requerido para este endpoint",
 *                            @OA\Schema(
 *                                type="string",
 *                                example="student"
 *                            )
 *                        ),
 *
 *     @OA\Response(
 *          response=200,
 *          description="Familiares obtenidos correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="children",
 *                              type="array",
 *                              description="Lista de hijos del usuario",
 *                              @OA\Items(ref="#/components/schemas/StudentParentsResponse")
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
 *
 * )
 */
public function getParents(){}

/**
 * @OA\Delete(
 *     path="/api/v1/parents/delete-parent/{parentId}",
 *     tags={"Parents"},
 *     summary="Eliminar una relación del estudiante con un familiar",
 *     operationId="deleteRelation",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                            name="X-User-Role",
 *                            in="header",
 *                            required=false,
 *                            description="Rol requerido para este endpoint",
 *                            @OA\Schema(
 *                                type="string",
 *                                example="student"
 *                            )
 *                        ),
 *     @OA\Parameter(
 *         name="parentId",
 *         in="path",
 *         description="ID del familiar a eliminar (por ejemplo, 4)",
 *         required=true,
 *         @OA\Schema(type="integer", example=4)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Relación eliminada correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Relación eliminada correctamente"
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
public function deleteParent(){}

}
