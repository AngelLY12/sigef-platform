<?php

namespace App\Swagger\controllers\Admin;

class AdminStudents
{
    /**
     * @OA\Patch (
     *     path="/api/v1/admin-actions/promote",
     *     summary="Incrementa el semestre de los alumnos y da de baja a quienes sobrepasan",
     *     description="Se hace un incremento en el semestre de todos los alumnos sin importar status y da de baja a quienes sobrepasan el semestre 12.",
     *     operationId="promotionStudents",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *           name="X-User-Role",
     *           in="header",
     *           required=false,
     *           description="Rol requerido para este endpoint",
     *           @OA\Schema(
     *               type="string",
     *               example="admin"
     *           )
     *       ),
     *       @OA\Parameter(
     *           name="X-User-Permission",
     *           in="header",
     *           required=false,
     *           description="Permiso requerido para este endpoint",
     *           @OA\Schema(
     *                type="string",
     *                example="promote.student"
     *            )
     *       ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Usuarios promovidos con exito",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="affected",
     *                              type="object",
     *                              @OA\Property(property="usuarios_promovidos", type="integer", example=27),
     *                              @OA\Property(property="usuarios_baja", type="integer", example=5)
     *                          )
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Se ejecutó la promoción de usuarios correctamente."
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
    public function promotion(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/attach-student",
     *     tags={"Admin"},
     *     summary="Asociar detalles de estudiante a un usuario existente",
     *     description="Permite asignar información académica (carrera, semestre, grupo, taller) a un usuario ya registrado.",
     *     operationId="attachStudentDetailToUser",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *            name="X-User-Role",
     *            in="header",
     *            required=false,
     *            description="Rol requerido para este endpoint",
     *            @OA\Schema(
     *                type="string",
     *                example="admin|supervisor"
     *            )
     *        ),
     *        @OA\Parameter(
     *            name="X-User-Permission",
     *            in="header",
     *            required=false,
     *            description="Permiso requerido para este endpoint",
     *            @OA\Schema(
     *                 type="string",
     *                 example="attach.student"
     *             )
     *        ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos necesarios para asociar un detalle de estudiante al usuario.",
     *         @OA\JsonContent(ref="#/components/schemas/AttachStudentRequest")
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Usuario asociado correctamente a un detalle de estudiante.",
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
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Se asociarón correctamente los datos al estudiante."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Error en la validación de datos.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="No autorizado para realizar esta acción.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Usuario o recurso no encontrado.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function attachStudent(){}

    /**
     * @OA\Get(
     *     path="/api/v1/admin-actions/get-student/{id}",
     *     tags={"Admin"},
     *     summary="Obtener detalles de estudiante a un usuario existente",
     *     description="Permite obtener información académica (carrera, grupo, taller) a un usuario ya registrado.",
     *     operationId="getStudentDetailToUser",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *             name="X-User-Role",
     *             in="header",
     *             required=false,
     *             description="Rol requerido para este endpoint",
     *             @OA\Schema(
     *                 type="string",
     *                 example="admin|supervisor"
     *             )
     *         ),
     *         @OA\Parameter(
     *             name="X-User-Permission",
     *             in="header",
     *             required=false,
     *             description="Permiso requerido para este endpoint",
     *             @OA\Schema(
     *                  type="string",
     *                  example="view.student"
     *              )
     *         ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="ID del estudiante del que se quieren los detalles.",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Detalles de estudiante encontrados correctamente.",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          @OA\Property(
     *                              property="user",
     *                              ref="#/components/schemas/DomainStudentDetail"
     *                          )
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="No autorizado para realizar esta acción.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Usuario o recurso no encontrado.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function getStudentDetails(){}

    /**
     * @OA\Patch(
     *     path="/api/v1/admin-actions/update-student/{id}",
     *     tags={"Admin"},
     *     summary="Actualizar detalles de estudiante a un usuario existente",
     *     description="Permite actualizar información académica (carrera, grupo, taller) a un usuario ya registrado.",
     *     operationId="updateStudentDetailToUser",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *              name="X-User-Role",
     *              in="header",
     *              required=false,
     *              description="Rol requerido para este endpoint",
     *              @OA\Schema(
     *                  type="string",
     *                  example="admin|supervisor"
     *              )
     *          ),
     *          @OA\Parameter(
     *              name="X-User-Permission",
     *              in="header",
     *              required=false,
     *              description="Permiso requerido para este endpoint",
     *              @OA\Schema(
     *                   type="string",
     *                   example="update.student"
     *               )
     *          ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos necesarios para actualizar un detalle de estudiante al usuario.",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateStudentRequest")
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Usuario actualizado correctamente con detalle de estudiante.",
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
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          example="Se actualizaron correctamente los detalles de estudiante."
     *                      )
     *                  )
     *              }
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Error en la validación de datos.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="No autorizado para realizar esta acción.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Usuario o recurso no encontrado.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error interno del servidor.",
     *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *      )
     * )
     */
    public function updateStudentDetails(){}

    /**
     * @OA\Post(
     *     path="/api/v1/admin-actions/import-students",
     *     summary="Importar detalles estudiantiles desde un archivo Excel",
     *      description="Permite subir un archivo Excel (.xlsx) con los detalles estudiantiles de los usuarios.
     *      Solo se insertarán filas con CURP existente en la base de datos y con career_id, n_control y semestre definidos.
     *      Columnas esperadas:
     *     0. curp (CURP del usuario)
     *     1. career_id (ID de la carrera)
     *     2. n_control (Número de control)
     *     3. semestre (Semestre)
     *     4. group (opcional grupo)
     *     5. workshop (opcional taller)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *               name="X-User-Role",
     *               in="header",
     *               required=false,
     *               description="Rol requerido para este endpoint",
     *               @OA\Schema(
     *                   type="string",
     *                   example="admin|supervisor"
     *               )
     *           ),
     *      @OA\Parameter(
     *               name="X-User-Permission",
     *               in="header",
     *               required=false,
     *               description="Permiso requerido para este endpoint",
     *               @OA\Schema(
     *                    type="string",
     *                    example="import.users"
     *                )
     *           ),
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
    public function importStudents(){}
}
