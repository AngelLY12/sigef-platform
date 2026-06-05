<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="DatabaseNotification",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         example="App\\Notifications\\NotificationBroadcast"
 *     ),
 *     @OA\Property(
 *         property="notifiable_type",
 *         type="string",
 *         example="App\\Models\\User"
 *     ),
 *     @OA\Property(
 *         property="notifiable_id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/NotificationPayload"
 *     ),
 *     @OA\Property(
 *         property="read_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 */
class DatabaseNotification {}
