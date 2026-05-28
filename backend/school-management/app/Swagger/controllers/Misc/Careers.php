<?php

namespace App\Swagger\controllers\Misc;

class Careers
{
/**
 * @OA\Get(
 *     path="/api/v1/careers",
 *     tags={"Careers"},
 *     summary="Obtener todas las carreras",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *            name="X-User-Role",
 *            in="header",
 *            required=false,
 *            description="Rol requerido para este endpoint",
 *            @OA\Schema(
 *                type="string",
 *                example="admin|supervisor|financial-staff"
 *            )
 *        ),
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
 *          description="Lista de carreras",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="careers",
 *                              type="array",
 *                              @OA\Items(ref="#/components/schemas/DomainCareer")
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Carreras encontradas."
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function getCareers(){}


/**
 * @OA\Get(
 *     path="/api/v1/careers/{id}",
 *     tags={"Careers"},
 *     summary="Obtener una carrera por ID",
 *     security={{"bearerAuth":{}}},
 *      @OA\Parameter(
 *             name="X-User-Role",
 *             in="header",
 *             required=false,
 *             description="Rol requerido para este endpoint",
 *             @OA\Schema(
 *                 type="string",
 *                 example="admin|supervisor|financial-staff"
 *             )
 *         ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de la carrera",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *      @OA\Parameter(
 *         name="forceRefresh",
 *         in="query",
 *         description="Forzar actualización del caché (true o false).",
 *         required=false,
 *         @OA\Schema(type="boolean", example=false)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Carrera encontrada",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="career",
 *                              ref="#/components/schemas/DomainCareer"
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Carrera encontrada."
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
public function getCareer(){}


/**
 * @OA\Post(
 *     path="/api/v1/careers",
 *     tags={"Careers"},
 *     summary="Crear una nueva carrera",
 *     security={{"bearerAuth":{}}},
 *      @OA\Parameter(
 *             name="X-User-Role",
 *             in="header",
 *             required=false,
 *             description="Rol requerido para este endpoint",
 *             @OA\Schema(
 *                 type="string",
 *                 example="admin|supervisor"
 *             )
 *         ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/CreateCareerRequest")
 *     ),
 *     @OA\Response(
 *          response=201,
 *          description="Carrera creada exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="career",
 *                              ref="#/components/schemas/DomainCareer"
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Carrera creada."
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=400, description="Solicitud incorrecta", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function createCareer(){}


/**
 * @OA\Patch(
 *     path="/api/v1/careers/{id}",
 *     tags={"Careers"},
 *     summary="Actualizar una carrera existente",
 *     security={{"bearerAuth":{}}},
 *      @OA\Parameter(
 *             name="X-User-Role",
 *             in="header",
 *             required=false,
 *             description="Rol requerido para este endpoint",
 *             @OA\Schema(
 *                 type="string",
 *                 example="admin|supervisor"
 *             )
 *         ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de la carrera",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/UpdateCareerRequest")
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Carrera actualizada exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="updated",
 *                              ref="#/components/schemas/DomainCareer"
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Carrera actualizada."
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(response=400, description="Solicitud incorrecta", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=403, description="No autorizado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=404, description="No encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=429, description="Demasiadas solicitudes", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *      @OA\Response(response=500, description="Error interno", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
public function updateCareer(){}


/**
 * @OA\Delete(
 *     path="/api/v1//careers/{id}",
 *     tags={"Careers"},
 *     summary="Eliminar una carrera",
 *     security={{"bearerAuth":{}}},
 *      @OA\Parameter(
 *             name="X-User-Role",
 *             in="header",
 *             required=false,
 *             description="Rol requerido para este endpoint",
 *             @OA\Schema(
 *                 type="string",
 *                 example="admin|supervisor"
 *             )
 *         ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de la carrera a eliminar",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="Carrera eliminada exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Carrera eliminada con éxito."
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
public function deleteCareer(){}

}

