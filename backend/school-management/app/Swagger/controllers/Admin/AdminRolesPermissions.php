<?php

namespace App\Swagger\controllers\Admin;

class AdminRolesPermissions
{
    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/update-permissions/{userId}",
     *     summary="Actualizar permisos a un usuario",
     *     description="Permite al administrador agregar o eliminar permisos a un usuario.",
     *     operationId="updateSingleUserPermissions",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *               name="X-User-Role",
     *               in="header",
     *               required=false,
     *               description="Rol requerido para este endpoint ",
     *               @OA\Schema(
     *                   type="string",
     *                   example="admin|supervisor"
     *               )
     *           ),
     *      @OA\Parameter(
     *               name="X-User-Permission",
     *               in="header",
     *               required=false,
     *               description="Permiso requerido para este endpoint ",
     *               @OA\Schema(
     *                    type="string",
     *                    example="sync.permissions"
     *                )
     *           ),
     *       @OA\Parameter(
     *           name="userId",
     *           in="path",
     *           description="ID del usuario a consultar",
     *           required=true,
     *           @OA\Schema(type="integer", example=2)
     *       ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos necesarios para actualizar permisos de usuario.",
     *         @OA\JsonContent(ref="#/components/schemas/UpdatePermissionsToUserRequest")
     *     ),
     *
     * @OA\Response(
     *          response=200,
     *          description="Respuesta de usuarios actualizados.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="updated",
     *                              type="array",
     *                              description="Usuarios con permisos actualizados",
     *                              @OA\Items(ref="#/components/schemas/PermissionsUpdatedToUserResponse")
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Permisos actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     * @OA\Response(
     *          response=422,
     *          description="Error de validación en los datos enviados",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     * @OA\Response(
     *          response=401,
     *          description="No autorizado: el usuario autenticado no tiene permiso para ejecutar esta acción",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     * @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function updatePermissionsToUser(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/updated-roles/{userId}",
     *     summary="Sincroniza roles de un usuario",
     *     description="Permite agregar o eliminar roles a un usuario.",
     *     tags={"Admin"},
     *     operationId="updateSingleUserRoles",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                 name="X-User-Role",
     *                 in="header",
     *                 required=false,
     *                 description="Rol requerido para este endpoint",
     *                 @OA\Schema(
     *                     type="string",
     *                     example="admin|supervisor"
     *                 )
     *             ),
     *        @OA\Parameter(
     *                 name="X-User-Permission",
     *                 in="header",
     *                 required=false,
     *                 description="Permiso requerido para este endpoint",
     *                 @OA\Schema(
     *                      type="string",
     *                      example="sync.roles"
     *                  )
     *             ),
     *     @OA\Parameter(
     *            name="userId",
     *            in="path",
     *            description="ID del usuario a consultar",
     *            required=true,
     *            @OA\Schema(type="integer", example=2)
     *        ),
     *
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(ref="#/components/schemas/UpdateRolesToUserRequest")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Roles actualizados correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="updated",
     *                              ref="#/components/schemas/RolesUpdatedToUserResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Roles actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Error de validación",
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
    public function updateRolesToUser(){}
    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/update-permissions",
     *     summary="Actualizar permisos a múltiples usuarios",
     *     description="Permite al administrador agregar o eliminar permisos a varios usuarios al mismo tiempo.
     *     Se puede especificar una lista de CURP o role (solo uno de los dos) y los permisos que se añadirán o eliminarán.",
     *     operationId="updateManyUserPermissions",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *               name="X-User-Role",
     *               in="header",
     *               required=false,
     *               description="Rol requerido para este endpoint ",
     *               @OA\Schema(
     *                   type="string",
     *                   example="admin|supervisor"
     *               )
     *           ),
     *      @OA\Parameter(
     *               name="X-User-Permission",
     *               in="header",
     *               required=false,
     *               description="Permiso requerido para este endpoint ",
     *               @OA\Schema(
     *                    type="string",
     *                    example="sync.permissions"
     *                )
     *           ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos necesarios para actualizar permisos de usuario.",
     *         @OA\JsonContent(ref="#/components/schemas/UpdatePermissionsRequest")
     *     ),
     *
     * @OA\Response(
     *          response=200,
     *          description="Respuesta de usuarios actualizados.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="users_permissions",
     *                              type="array",
     *                              description="Usuarios con permisos actualizados",
     *                              @OA\Items(ref="#/components/schemas/UserWithUpdatedPermissionsResponse")
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Permisos actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     * @OA\Response(
     *          response=422,
     *          description="Error de validación en los datos enviados",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     * @OA\Response(
     *          response=401,
     *          description="No autorizado: el usuario autenticado no tiene permiso para ejecutar esta acción",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     * @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function updatePermissions(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/updated-roles",
     *     summary="Sincroniza roles de múltiples usuarios",
     *     description="Permite agregar o eliminar roles a varios usuarios simultáneamente.",
     *     tags={"Admin"},
     *     operationId="updateManyUserRoles",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                 name="X-User-Role",
     *                 in="header",
     *                 required=false,
     *                 description="Rol requerido para este endpoint",
     *                 @OA\Schema(
     *                     type="string",
     *                     example="admin|supervisor"
     *                 )
     *             ),
     *        @OA\Parameter(
     *                 name="X-User-Permission",
     *                 in="header",
     *                 required=false,
     *                 description="Permiso requerido para este endpoint",
     *                 @OA\Schema(
     *                      type="string",
     *                      example="sync.roles"
     *                  )
     *             ),
     *
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(ref="#/components/schemas/UpdateRolesRequest")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Roles actualizados correctamente",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="users_roles",
     *                              ref="#/components/schemas/UserWithUpdatedRoleResponse"
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Roles actualizados correctamente."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Error de validación",
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
    public function updateRoles(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/permissions/by-curps",
     *     summary="Mostrar permisos existentes para usuarios especificos",
     *     description="Permite al administrador ver todos los permisos registrados.",
     *     operationId="showAllPermissionsByCurps",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *      @OA\Parameter(
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
     *                          example="view.permissions"
     *                      )
     *                 ),
     *
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/FindPermissionsByCurpsRequest"
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Permisos obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="permissions",
     *                              allOf={
     *                                  @OA\Schema(ref="#/components/schemas/PermissionsByUsers"),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="roles",
     *                                          type="array",
     *                                          example={"admin","user","editor"},
     *                                          @OA\Items(
     *                                              type="string"
     *                                          )
     *                                      )
     *                                  ),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="users",
     *                                          type="array",
     *                                          @OA\Items(
     *                                              type="object",
     *                                              @OA\Property(property="id", type="integer", example=1),
     *                                              @OA\Property(property="fullName", type="string", example="Ana García"),
     *                                              @OA\Property(property="curp", type="string", example="GAAA900101HDFRRN05")
     *                                          )
     *                                      )
     *                                  ),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="permissions",
     *                                          type="array",
     *                                          @OA\Items(ref="#/components/schemas/PermissionToDisplay")
     *                                      )
     *                                  )
     *                              }
     *                          )
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
     *          response=422,
     *          description="Error de validación",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function findPermissions(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/permissions/by-role",
     *     summary="Mostrar permisos existentes por role",
     *     description="Permite al administrador ver todos los permisos registrados por role.",
     *     operationId="showAllPermissionsByRole",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *      @OA\Parameter(
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
     *                          example="view.permissions"
     *                      )
     *                 ),
     *
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/FindPermissionsByCurpsRequest"
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Permisos obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="permissions",
     *                              allOf={
     *                                  @OA\Schema(ref="#/components/schemas/PermissionsByRole"),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="role",
     *                                          type="string",
     *                                          example="student"
     *                                      )
     *                                  ),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="usersCount",
     *                                          type="integer",
     *                                          example=130
     *                                      )
     *                                  ),
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="permissions",
     *                                          type="array",
     *                                          @OA\Items(ref="#/components/schemas/PermissionToDisplay")
     *                                      )
     *                                  )
     *                              }
     *                          )
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
     *          response=422,
     *          description="Error de validación",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function findPermissionsByRole(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/permissions/by-user/{userId}",
     *     summary="Mostrar permisos existentes para los roles del usuario",
     *     description="Permite al administrador ver todos los permisos registrados para el usuario.",
     *     operationId="showAllPermissionsByUser",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *      @OA\Parameter(
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
     *                          example="view.permissions"
     *                      )
     *                 ),
     *     @OA\Parameter(
     *          name="userId",
     *          in="path",
     *          description="ID del usuario a consultar",
     *          required=true,
     *          @OA\Schema(type="integer", example=2)
     *      ),
     *
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/FindPermissionsToUserRequest"
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Permisos obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="permissions",
     *                              allOf={
     *
     *                                  @OA\Schema(
     *                                      @OA\Property(
     *                                          property="permissions",
     *                                          type="array",
     *                                          @OA\Items(ref="#/components/schemas/PermissionToDisplay")
     *                                      )
     *                                  )
     *                              }
     *                          )
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
     *          response=422,
     *          description="Error de validación",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function findPermissionsByUser(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/find-roles",
     *     summary="Mostrar roles existentes",
     *     description="Permite al administrador ver todos los roles registrados.",
     *     operationId="showAllRoles",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                      name="X-User-Role",
     *                      in="header",
     *                      required=false,
     *                      description="Rol requerido para este endpoint",
     *                      @OA\Schema(
     *                          type="string",
     *                          example="admin|supervisor"
     *                      )
     *                  ),
     *             @OA\Parameter(
     *                      name="X-User-Permission",
     *                      in="header",
     *                      required=false,
     *                      description="Permiso requerido para este endpoint",
     *                      @OA\Schema(
     *                           type="string",
     *                           example="view.roles"
     *                       )
     *                  ),
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
     *          description="Roles obtenidos correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="roles",
     *                              type="array",
     *                              description="Lista de roles disponibles.",
     *                              @OA\Items(ref="#/components/schemas/Role")
     *                          )
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
    public function findRoles(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/roles/{id}",
     *     summary="Mostrar rol por ID",
     *     description="Permite al administrador ver la información de un rol específico por su identificador.",
     *     operationId="showRoleById",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                      name="X-User-Role",
     *                      in="header",
     *                      required=false,
     *                      description="Rol requerido para este endpoint",
     *                      @OA\Schema(
     *                          type="string",
     *                          example="admin|supervisor"
     *                      )
     *                  ),
     *             @OA\Parameter(
     *                      name="X-User-Permission",
     *                      in="header",
     *                      required=false,
     *                      description="Permiso requerido para este endpoint",
     *                      @OA\Schema(
     *                           type="string",
     *                           example="view.roles"
     *                       )
     *                  ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del rol a consultar",
     *         required=true,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Rol obtenido correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="role",
     *                              ref="#/components/schemas/Role"
     *                          )
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Rol no encontrado",
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
    public function findRole(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/permissions/{id}",
     *     summary="Mostrar permiso por ID",
     *     description="Permite al administrador ver la información de un permiso específico por su identificador.",
     *     operationId="showPermissionById",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *                      name="X-User-Role",
     *                      in="header",
     *                      required=false,
     *                      description="Rol requerido para este endpoint",
     *                      @OA\Schema(
     *                          type="string",
     *                          example="admin|supervisor"
     *                      )
     *                  ),
     *             @OA\Parameter(
     *                      name="X-User-Permission",
     *                      in="header",
     *                      required=false,
     *                      description="Permiso requerido para este endpoint",
     *                      @OA\Schema(
     *                           type="string",
     *                           example="view.permissions"
     *                       )
     *                  ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del permiso a consultar",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Permiso obtenido correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="permission",
     *                              ref="#/components/schemas/Permission"
     *                          )
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Permiso no encontrado",
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
    public function findPermission(){}

}
