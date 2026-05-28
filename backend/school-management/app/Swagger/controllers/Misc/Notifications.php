<?php

namespace App\Swagger\controllers\Misc;

class Notifications
{

/**
 * @OA\Get(
 *     path="/api/v1/notifications",
 *     operationId="getUserNotifications",
 *     tags={"Notifications"},
 *     summary="Obtener notificaciones leídas paginadas del usuario autenticado",
 *     description="Retorna una lista paginada de las notificaciones LEÍDAS del usuario. Las notificaciones no leídas se obtienen en el endpoint /unread.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número de página",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Notificaciones por página",
 *         required=false,
 *         @OA\Schema(type="integer", default=20, maximum=100)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Notificaciones leídas obtenidas exitosamente",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(
 *                             property="notifications",
 *                             type="object",
 *                             @OA\Property(
 *                                 property="data",
 *                                 type="array",
 *                                 @OA\Items(
 *                                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *                                     @OA\Property(property="type", type="string", example="App\\Notifications\\PaymentConceptUpdated"),
 *                                     @OA\Property(property="notifiable_type", type="string", example="App\\Models\\User"),
 *                                     @OA\Property(property="notifiable_id", type="integer", example=1),
 *                                     @OA\Property(property="data", type="object",
 *                                         @OA\Property(property="title", type="string", example="Actualización del concepto de pago"),
 *                                         @OA\Property(property="message", type="string", example="El concepto 'Matrícula' (1500.00 MXN) ha sido actualizado"),
 *                                         @OA\Property(property="concept_id", type="integer", example=1),
 *                                         @OA\Property(property="concept_name", type="string", example="Matrícula"),
 *                                         @OA\Property(property="amount", type="number", format="float", example=1500.00),
 *                                         @OA\Property(property="type", type="string", example="payment_concept_changed"),
 *                                         @OA\Property(property="created_at", type="string", format="date-time")
 *                                     ),
 *                                     @OA\Property(property="read_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
 *                                     @OA\Property(property="created_at", type="string", format="date-time"),
 *                                     @OA\Property(property="updated_at", type="string", format="date-time")
 *                                 )
 *                             ),
 *                             @OA\Property(property="current_page", type="integer"),
 *                             @OA\Property(property="last_page", type="integer"),
 *                             @OA\Property(property="per_page", type="integer"),
 *                             @OA\Property(property="total", type="integer"),
 *                             @OA\Property(property="links", type="object")
 *                         ),
 *                         @OA\Property(property="unread_count", type="integer", example=5),
 *                         @OA\Property(property="read_count", type="integer", example=15)
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autenticado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Demasiadas solicitudes",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function index(){}

/**
 * @OA\Get(
 *     path="/api/v1/notifications/unread",
 *     operationId="getUnreadNotifications",
 *     tags={"Notifications"},
 *     summary="Obtener notificaciones no leídas",
 *     description="Retorna todas las notificaciones no leídas del usuario",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Notificaciones no leídas obtenidas",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(
 *                             property="notifications",
 *                             type="array",
 *                             @OA\Items(
 *                                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *                                 @OA\Property(property="type", type="string", example="App\\Notifications\\PaymentConceptUpdated"),
 *                                 @OA\Property(property="notifiable_type", type="string", example="App\\Models\\User"),
 *                                 @OA\Property(property="notifiable_id", type="integer", example=1),
 *                                 @OA\Property(property="data", type="object"),
 *                                 @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *                                 @OA\Property(property="created_at", type="string", format="date-time")
 *                             )
 *                         ),
 *                         @OA\Property(property="count", type="integer", example=3)
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autenticado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Demasiadas solicitudes",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function unread(){}

/**
 * @OA\Post(
 *     path="/api/v1/notifications/mark-as-read",
 *     operationId="markAllAsRead",
 *     tags={"Notifications"},
 *     summary="Marcar todas las notificaciones como leídas",
 *     description="Marca todas las notificaciones no leídas del usuario como leídas",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Todas las notificaciones marcadas como leídas",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="unread_count", type="integer", example=0)
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autenticado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Demasiadas solicitudes",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/v1/notifications/mark-as-read/{id}",
 *     operationId="markNotificationAsRead",
 *     tags={"Notifications"},
 *     summary="Marcar una notificación específica como leída",
 *     description="Marca una notificación específica como leída por su ID",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="UUID de la notificación",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Notificación marcada como leída",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="unread_count", type="integer", example=4)
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Notificación no encontrada",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ErrorResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(property="message", type="string", example="Notificación no encontrada")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autenticado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Demasiadas solicitudes",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function markAsRead(){}

/**
 * @OA\Delete(
 *     path="/api/v1/notifications/{id}",
 *     operationId="deleteNotification",
 *     tags={"Notifications"},
 *     summary="Eliminar una notificación",
 *     description="Elimina una notificación específica del usuario",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="UUID de la notificación a eliminar",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Notificación eliminada exitosamente",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Notificación no encontrada",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ErrorResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(property="message", type="string", example="Notificación no encontrada")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autenticado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Demasiadas solicitudes",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function destroy(){}
}
