<?php

namespace App\Swagger\controllers\Admin;

class AdminUsers
{
    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/register",
     *     summary="Registrar un nuevo usuario",
     *     description="Crea un nuevo usuario en el sistema con los datos proporcionados.",
     *     operationId="adminRegisterUser",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *          name="X-User-Role",
     *          in="header",
     *          required=false,
     *          description="Rol requerido para este endpoint",
     *          @OA\Schema(
     *              type="string",
     *              example="admin"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="X-User-Permission",
     *          in="header",
     *          required=false,
     *          description="Permiso requerido para este endpoint",
     *          @OA\Schema(
     *               type="string",
     *               example="create.user"
     *           )
     *      ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos necesarios para el registro del usuario. Nota: La contraseña no debe ser incluida en la request, se genera para cada usuario desde el sistema",
     *         @OA\JsonContent(ref="#/components/schemas/RegisterUserRequest")
     *     ),
     *
     *     @OA\Response(
     *          response=201,
     *          description="Usuario creado con éxito",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="El usuario ha sido creado con éxito."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Error en la validación de datos",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Error inesperado en el servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function registerUser(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/import-users",
     *     summary="Importar usuarios desde un archivo Excel",
     *     description="Permite subir un archivo Excel (.xlsx) con los datos de los usuarios y sus detalles estudiantiles.
     *     El archivo debe contener las columnas en el siguiente orden:
     *     0. name (Nombre)
     *     1. last_name (Apellidos)
     *     2. email (Correo electrónico)
     *     3. phone_number (Teléfono, formato +52)
     *     4. birthdate (Fecha de nacimiento, formato YYYY-MM-DD)
     *     5. gender (Género, hombre/mujer, opcional)
     *     6. curp (CURP)
     *     7. street (Calle)
     *     8. city (Ciudad)
     *     9. state (Estado)
     *     10. zip_code (Código postal)
     *     11. blood_type (Tipo de sangre, A+, B+, O-, etc, opcional)
     *     12. registration_date (Fecha de registro, si no se especifica se usa la actual, formato YYYY-MM-DD)
     *     13. status (Estado del usuario, por defecto 'activo')
     *     14. career_id (ID de la carrera)
     *     15. n_control (Número de control)
     *     16. semestre (Semestre)
     *     17. group (Grupo)
     *     18. workshop (Taller)
     *     El género, dirección y tipo de sangre son opcionales, ademas, los detalles de estudiante del 14 a 18 no son obligatorios, si decides ponerlos  los primeros tres son obligatorios, ID de la carrera, número de control y semestre
     *     ",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *              name="X-User-Role",
     *              in="header",
     *              required=false,
     *              description="Rol requerido para este endpoint",
     *              @OA\Schema(
     *                  type="string",
     *                  example="admin|supervisor"
     *              )
     *          ),
     *      @OA\Parameter(
     *              name="X-User-Permission",
     *              in="header",
     *              required=false,
     *              description="Permiso requerido para este endpoint",
     *              @OA\Schema(
     *                   type="string",
     *                   example="import.users"
     *               )
     *          ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Archivo Excel (.xlsx) con las columnas en el orden especificado"
     *                 )
     *             )
     *         )
     *     ),
     * @OA\Response(
     *          response=200,
     *          description="Importación completada",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="summary",
     *                              ref="#/components/schemas/ImportResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Usuarios importados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     * @OA\Response(
     *          response=400,
     *          description="Error en la validación o formato del archivo.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     * @OA\Response(
     *          response=422,
     *          description="Error de validación de datos",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     * @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function import(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/show-users",
     *     summary="Mostrar usuarios existentes",
     *     description="Permite al administrador ver a todos los usuarios registrados, junto con sus roles, permisos y detalles académicos (si aplica).",
     *     operationId="showAllUsers",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                name="X-User-Role",
     *                in="header",
     *                required=false,
     *                description="Rol requerido para este endpoint",
     *                @OA\Schema(
     *                    type="string",
     *                    example="admin|supervisor"
     *                )
     *            ),
     *       @OA\Parameter(
     *                name="X-User-Permission",
     *                in="header",
     *                required=false,
     *                description="Permiso requerido para este endpoint",
     *                @OA\Schema(
     *                     type="string",
     *                     example="view.users"
     *                 )
     *            ),
     *
     *     @OA\Parameter(
     *         name="forceRefresh",
     *         in="query",
     *         description="Forzar actualización del caché (true o false).",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         description="Cantidad de usuarios por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página a mostrar",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter (
     *           name="status",
     *           in="query",
     *           description="Filtrar usuarios por estatus",
     *           required=false,
     *           @OA\Schema(ref="#/components/schemas/UserStatus")
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="users",
     *                              allOf={
     *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="items",
     *                                          type="array",
     *                                          @OA\Items(ref="#/components/schemas/UserListItemResponse")
     *                                      )
     *                                  )
     *                              }
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Usuarios encontrados."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function showUsers(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/show-users/{id}",
     *     summary="Mostrar datos extra del usuario existente",
     *     description="Permite al administrador ver datos extra del usuario, junto con sus roles, permisos y detalles académicos (si aplica).",
     *     operationId="showUserDetail",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                name="X-User-Role",
     *                in="header",
     *                required=false,
     *                description="Rol requerido para este endpoint",
     *                @OA\Schema(
     *                    type="string",
     *                    example="admin|supervisor"
     *                )
     *            ),
     *       @OA\Parameter(
     *                name="X-User-Permission",
     *                in="header",
     *                required=false,
     *                description="Permiso requerido para este endpoint",
     *                @OA\Schema(
     *                     type="string",
     *                     example="view.users"
     *                 )
     *            ),
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
     *         in="query",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="user",
     *                              ref="#/components/schemas/UserExtraDataResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Usuario encontrado."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function showUser(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/users-summary",
     *     summary="Mostrar resuúmen de usuarios",
     *     description="Permite al administrador ver un resumen de los usuarios del sistema.",
     *     operationId="showUsersSummary",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                name="X-User-Role",
     *                in="header",
     *                required=false,
     *                description="Rol requerido para este endpoint",
     *                @OA\Schema(
     *                    type="string",
     *                    example="admin|supervisor"
     *                )
     *            ),
     *       @OA\Parameter(
     *                name="X-User-Permission",
     *                in="header",
     *                required=false,
     *                description="Permiso requerido para este endpoint",
     *                @OA\Schema(
     *                     type="string",
     *                     example="view.users"
     *                 )
     *            ),
     *
     *     @OA\Parameter(
     *         name="forceRefresh",
     *         in="query",
     *         description="Forzar actualización del caché (true o false).",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *          name="only_this_year",
     *          in="query",
     *          description="Mostrar datos de este año (true o false).",
     *          required=false,
     *          @OA\Schema(type="boolean", example=false)
     *      ),
     *
     *
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="summary",
     *                              ref="#/components/schemas/UsersAdminSummary"
     *
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Resumen de usuarios obtenido."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function usersSummary(){}


    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/activate-users",
     *     summary="Activa múltiples usuarios",
     *     description="Cambia el estado de los usuarios seleccionados a 'activado'.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *                  name="X-User-Role",
     *                  in="header",
     *                  required=false,
     *                  description="Rol requerido para este endpoint",
     *                  @OA\Schema(
     *                      type="string",
     *                      example="admin|supervisor"
     *                  )
     *              ),
     *         @OA\Parameter(
     *                  name="X-User-Permission",
     *                  in="header",
     *                  required=false,
     *                  description="Permiso requerido para este endpoint",
     *                  @OA\Schema(
     *                       type="string",
     *                       example="activate.users"
     *                   )
     *              ),
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(ref="#/components/schemas/ChangeUserStatusRequest")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios activados correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="concept",
     *                              ref="#/components/schemas/UserChangedStatusResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Estatus de usuarios actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=409,
     *          description="Conflicto en los datos",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function activateUsers(){}


    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/delete-users",
     *     summary="Elimina múltiples usuarios",
     *     description="Cambia el estado de los usuarios seleccionados a 'eliminado'.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *                   name="X-User-Role",
     *                   in="header",
     *                   required=false,
     *                   description="Rol requerido para este endpoint",
     *                   @OA\Schema(
     *                       type="string",
     *                       example="admin|supervisor"
     *                   )
     *               ),
     *          @OA\Parameter(
     *                   name="X-User-Permission",
     *                   in="header",
     *                   required=false,
     *                   description="Permiso requerido para este endpoint",
     *                   @OA\Schema(
     *                        type="string",
     *                        example="delete.users"
     *                    )
     *               ),
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(ref="#/components/schemas/ChangeUserStatusRequest")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios eliminados correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="concept",
     *                              ref="#/components/schemas/UserChangedStatusResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Estatus de usuarios actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=409,
     *          description="Conflicto en los datos",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function deleteUsers(){}


    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/disable-users",
     *     summary="Da de baja múltiples usuarios",
     *     description="Cambia el estado de los usuarios seleccionados a 'baja'.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *                    name="X-User-Role",
     *                    in="header",
     *                    required=false,
     *                    description="Rol requerido para este endpoint",
     *                    @OA\Schema(
     *                        type="string",
     *                        example="admin|supervisor"
     *                    )
     *                ),
     *           @OA\Parameter(
     *                    name="X-User-Permission",
     *                    in="header",
     *                    required=false,
     *                    description="Permiso requerido para este endpoint",
     *                    @OA\Schema(
     *                         type="string",
     *                         example="disable.users"
     *                     )
     *                ),
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(ref="#/components/schemas/ChangeUserStatusRequest")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios dados de baja correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="concept",
     *                              ref="#/components/schemas/UserChangedStatusResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Estatus de usuarios actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=409,
     *          description="Conflicto en los datos",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function disableUsers(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/temporary-disable-users",
     *     summary="Da de baja temporal múltiples usuarios",
     *     description="Cambia el estado de los usuarios seleccionados a 'baja-temporal'.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *                     name="X-User-Role",
     *                     in="header",
     *                     required=false,
     *                     description="Rol requerido para este endpoint",
     *                     @OA\Schema(
     *                         type="string",
     *                         example="admin|supervisor"
     *                     )
     *                 ),
     *            @OA\Parameter(
     *                     name="X-User-Permission",
     *                     in="header",
     *                     required=false,
     *                     description="Permiso requerido para este endpoint",
     *                     @OA\Schema(
     *                          type="string",
     *                          example="disable.users"
     *                      )
     *                 ),
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(ref="#/components/schemas/ChangeUserStatusRequest")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Usuarios dados de baja correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="concept",
     *                              ref="#/components/schemas/UserChangedStatusResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Estatus de usuarios actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=409,
     *          description="Conflicto en los datos",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="No autorizado",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function temporaryDisableUsers(){}

}
