<?php

namespace App\Swagger\controllers\Misc;

class Users
{
/**
 * @OA\Patch(
 *     path="/api/v1/users/update",
 *     tags={"Users"},
 *     summary="Actualizar los datos generales de un usuario",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Usuario actualizado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="user",
 *                              ref="#/components/schemas/DomainUser"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Errores de validación",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=400,
 *          description="Error en la solicitud",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="No autenticado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="No autorizado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 * )
 */
public function updateUser(){}


/**
 * @OA\Patch(
 *     path="/api/v1/users/update/password",
 *     tags={"Users"},
 *     summary="Actualizar la contraseña de un usuario",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             ref="#/components/schemas/UpdatePasswordRequest"
 *         )
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Contraseña actualizada correctamente",
 *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Errores de validación",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=400,
 *          description="Contraseña actual incorrecta",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="No autenticado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="No autorizado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 * )
 */
public function updatePassword(){}


/**
 * @OA\Get(
 *     path="/api/v1/users/user",
 *     summary="Obtener usuario autenticado",
 *     description="Devuelve la información del usuario autenticado en el sistema.",
 *     tags={"Users"},
 *     security={{"bearerAuth":{}}},
 *
 *      @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Usuario autenticado encontrado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="user",
 *                              ref="#/components/schemas/UserAuthResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Usuario no encontrado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="No autenticado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="No autorizado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=429,
 *          description="Demasiadas solicitudes",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Error interno del servidor",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 *  )
 */
public function getUser(){}

/**
 * @OA\Get(
 *     path="/api/v1/users/student-details",
 *     summary="Obtener detalles de estudiante del usuario autenticado",
 *     description="Devuelve la información del estudiante autenticado en el sistema.",
 *     tags={"Users"},
 *     security={{"bearerAuth":{}}},
 *
 *      @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Usuario autenticado encontrado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="student_details",
 *                              ref="#/components/schemas/StudentDetailDTO"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Usuario no encontrado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="No autenticado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="No autorizado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=429,
 *          description="Demasiadas solicitudes",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Error interno del servidor",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 *  )
 */
public function getStudentDetails(){}

}

