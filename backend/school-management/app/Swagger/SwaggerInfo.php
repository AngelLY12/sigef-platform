<?php
namespace App\Swagger;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="School Management API",
 *     description="Documentación de la API para el sistema de gestión escolar",
 *     @OA\Contact(
 *         email="soporte@cbta71.edu.mx",
 *         name="Equipo de desarrollo CBTa71"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:80",
 *     description="Servidor local"
 * )
 *
 * @OA\Server(
 *     url="https://nginx-production-728f.up.railway.app",
 *     description="Servidor de producción"
 * )
 *
 * @OA\PathItem(
 *      path="/api"
 *  )
 */
class SwaggerInfo{}
