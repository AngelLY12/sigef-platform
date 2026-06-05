<?php
namespace App\Swagger;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="SIGEF API",
 *     description="School and Financial Management System API",
 *     @OA\Contact(
 *         name="Angel López Yáñez"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:80",
 *     description="Local development server"
 * )
 *
 * @OA\Server(
 *     url="https://api.example.com",
 *     description="Production server"
 * )
 *
 * @OA\PathItem(
 *      path="/api"
 *  )
 */
class SwaggerInfo{}
