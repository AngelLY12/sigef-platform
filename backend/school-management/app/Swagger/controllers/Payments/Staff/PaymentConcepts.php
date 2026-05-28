<?php

namespace App\Swagger\controllers\Payments\Staff;

class PaymentConcepts
{
/**
 * @OA\Get(
 *     path="/api/v1/concepts",
 *     summary="Listar conceptos de pago",
 *     description="Obtiene una lista paginada de conceptos de pago, con filtros opcionales por estado y control de caché.",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                         name="X-User-Role",
 *                         in="header",
 *                         required=false,
 *                         description="Rol requerido para este endpoint",
 *                         @OA\Schema(
 *                             type="string",
 *                             example="financial-staff"
 *                         )
 *                     ),
 *                     @OA\Parameter(
 *                         name="X-User-Permission",
 *                         in="header",
 *                         required=false,
 *                         description="Permiso requerido para este endpoint",
 *                         @OA\Schema(
 *                              type="string",
 *                              example="view.concepts"
 *                          )
 *                     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         ref="#/components/schemas/PaymentConceptStatus"
 *     ),
 *     @OA\Parameter(
 *         name="perPage",
 *         in="query",
 *         description="Cantidad de registros por página",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página a obtener",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Si es true, fuerza actualización de caché",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Listado de conceptos de pago obtenido exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concepts",
 *                              allOf={
 *                                  @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
 *                                  @OA\Schema(
 *                                      @OA\Property(
 *                                          property="items",
 *                                          type="array",
 *                                          @OA\Items(ref="#/components/schemas/ConceptsListItem")
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
public function concepts(){}

/**
 * @OA\Get(
 *     path="/api/v1/concepts/{id}",
 *     summary="Buscar concepto de pago por ID",
 *     description="Obtiene la información de un concepto de pago específico mediante su identificador.",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                         name="X-User-Role",
 *                         in="header",
 *                         required=false,
 *                         description="Rol requerido para este endpoint",
 *                         @OA\Schema(
 *                             type="string",
 *                             example="financial-staff"
 *                         )
 *                     ),
 *                     @OA\Parameter(
 *                         name="X-User-Permission",
 *                         in="header",
 *                         required=false,
 *                         description="Permiso requerido para este endpoint",
 *                         @OA\Schema(
 *                              type="string",
 *                              example="view.concepts"
 *                          )
 *                     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID del concepto a buscar",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto encontrado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/ConceptToDisplay"
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
public function getConcept(){}

/**
 * @OA\Get(
 *     path="/api/v1/concepts/relations/{id}",
 *     summary="Buscar relaciones del concepto de pago por ID",
 *     description="Obtiene la información de las relaciones de un concepto de pago específico mediante su identificador.",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                         name="X-User-Role",
 *                         in="header",
 *                         required=false,
 *                         description="Rol requerido para este endpoint",
 *                         @OA\Schema(
 *                             type="string",
 *                             example="financial-staff"
 *                         )
 *                     ),
 *                     @OA\Parameter(
 *                         name="X-User-Permission",
 *                         in="header",
 *                         required=false,
 *                         description="Permiso requerido para este endpoint",
 *                         @OA\Schema(
 *                              type="string",
 *                              example="view.concepts"
 *                          )
 *                     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID del concepto a buscar",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto encontrado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/ConceptRelationsToDisplay"
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
public function getConceptRelations(){}

/**
 * @OA\Get(
 *     path="/api/v1/concepts/search/controls",
 *     summary="Buscar números de control de estudiantes",
 *     description="Realiza una búsqueda de números de control de estudiantes activos. Los resultados se cachean por defecto.",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         required=true,
 *         description="Texto para buscar en números de control (búsqueda por prefijo)",
 *         @OA\Schema(type="string", minLength=1, maxLength=50, example="2023")
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         required=false,
 *         description="Límite de resultados (1-50), por defecto 15",
 *         @OA\Schema(type="integer", minimum=1, maximum=50, default=15)
 *     ),
 *     @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         required=false,
 *         description="Forzar refresco del cache",
 *         @OA\Schema(type="boolean", default=false)
 *     ),
 *      @OA\Response(
 *           response=200,
 *           description="Búsqueda exitosa",
 *           @OA\JsonContent(
 *               allOf={
 *                   @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                   @OA\Schema(
 *                       @OA\Property(
 *                           property="data",
 *                           type="object",
 *                           @OA\Property(
 *                               property="concept",
 *                               ref="#/components/schemas/StudentControlNumber"
 *                           )
 *                       )
 *                   )
 *               }
 *           )
 *       ),
 *       @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *       @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *       @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *       @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *       @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function getControls(){}


/**
 * @OA\Post(
 *     path="/api/v1/concepts",
 *     summary="Crear un nuevo concepto de pago",
 *     description="Crea un nuevo concepto de pago y lo asocia con las entidades correspondientes (carreras, semestres, estudiantes).",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="create.concepts"
 *                           )
 *                      ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/StorePaymentConceptRequest")
 *     ),
 *     @OA\Response(
 *          response=201,
 *          description="Concepto de pago creado exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/CreatePaymentConceptResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function createConcept(){}


/**
 * @OA\Put(
 *     path="/api/v1/concepts/{id}",
 *     summary="Actualizar un concepto de pago",
 *     description="Actualiza los datos de un concepto de pago existente. Todos los campos son opcionales (usar 'sometimes'), excepto el id en la ruta.",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="update.concepts"
 *                           )
 *                      ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID del concepto de pago",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/UpdatePaymentConceptRequest")
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto actualizado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/UpdatePaymentConceptResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function updateConcept(){}

/**
 * @OA\Patch(
 *     path="/api/v1/concepts/update-relations/{id}",
 *     summary="Actualizar relaciones de un concepto de pago",
 *     description="Actualiza las relaciones de un concepto de pago existente.",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="update.concepts"
 *                           )
 *                      ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID del concepto de pago",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/UpdatePaymentConceptRelationsRequest")
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Relaciones del concepto actualizadas correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/UpdatePaymentConceptRelationsResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=422, description="Error de validación", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function updateRelations(){}


/**
 * @OA\Post(
 *     path="/api/v1/concepts/{concept}/finalize",
 *     summary="Finalizar concepto de pago",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="finalize.concepts"
 *                           )
 *                      ),
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto finalizado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/ConceptChangeStatusResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function finalizeConcept(){}


/**
 * @OA\Post(
 *     path="/api/v1/concepts/{concept}/disable",
 *     summary="Deshabilitar un concepto de pago",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="disable.concepts"
 *                           )
 *                      ),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto deshabilitado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/ConceptChangeStatusResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function disableConcept(){}


/**
 * @OA\Post(
 *     path="/api/v1/concepts/{concept}/activate",
 *     summary="Habilitar un concepto de pago",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="activate.concepts"
 *                           )
 *                      ),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto activado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="concept",
 *                              ref="#/components/schemas/ConceptChangeStatusResponse"
 *                          )
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function activateConcept(){}


/**
 * @OA\Delete(
 *     path="/api/v1/concepts/{id}/eliminate",
 *     summary="Eliminar concepto de pago (físicamente)",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *                          name="X-User-Role",
 *                          in="header",
 *                          required=false,
 *                          description="Rol requerido para este endpoint",
 *                          @OA\Schema(
 *                              type="string",
 *                              example="financial-staff"
 *                          )
 *                      ),
 *                      @OA\Parameter(
 *                          name="X-User-Permission",
 *                          in="header",
 *                          required=false,
 *                          description="Permiso requerido para este endpoint",
 *                          @OA\Schema(
 *                               type="string",
 *                               example="eliminate.concepts"
 *                           )
 *                      ),
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *          response=200,
 *          description="Concepto de pago eliminado correctamente",
 *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *      ),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function deleteConcept(){}


 /**
 * @OA\Post(
  *     path="/api/v1/concepts/{concept}/eliminateLogical",
 *     summary="Eliminar concepto de pago (lógicamente)",
 *     tags={"Payment Concepts"},
 *     security={{"bearerAuth":{}}},
  *     @OA\Parameter(
  *                          name="X-User-Role",
  *                          in="header",
  *                          required=false,
  *                          description="Rol requerido para este endpoint",
  *                          @OA\Schema(
  *                              type="string",
  *                              example="financial-staff"
  *                          )
  *                      ),
  *                      @OA\Parameter(
  *                          name="X-User-Permission",
  *                          in="header",
  *                          required=false,
  *                          description="Permiso requerido para este endpoint",
  *                          @OA\Schema(
  *                               type="string",
  *                               example="eliminate.logical.concepts"
  *                           )
  *                      ),
 *     @OA\Response(
  *          response=200,
  *          description="Concepto eliminado correctamente",
  *          @OA\JsonContent(
  *              allOf={
  *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
  *                  @OA\Schema(
  *                      @OA\Property(
  *                          property="data",
  *                          type="object",
  *                          @OA\Property(
  *                              property="concept",
  *                              ref="#/components/schemas/ConceptChangeStatusResponse"
  *                          )
  *                      )
  *                  )
  *              }
  *          )
  *      ),
  *      @OA\Response(response=409, description="Conflicto", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
  *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
  *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
  *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
  *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
  *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function deleteLogicalConcept(){}

}
