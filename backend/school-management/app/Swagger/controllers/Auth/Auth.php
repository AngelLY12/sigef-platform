<?php

namespace App\Swagger\controllers\Auth;

class Auth
{
/**
 * @OA\Post(
 *     path="/api/email/verification-notification",
 *     tags={"Auth"},
 *     summary="Enviar enlace de verificación de correo",
 *     description="Envía un correo con el enlace de verificación si el usuario aún no ha verificado su email.",
 *     operationId="sendEmailVerificationNotification",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *          response=200,
 *          description="Enlace de verificación enviado exitosamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(property="status", type="string", example="verification-link-sent")
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(
 *          response=302,
 *          description="Redirección al dashboard si el email ya está verificado"
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="No autenticado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 * )
 */
public function emailVerificationNotify(){}


/**
*
* @OA\Post(
*     path="/api/reset-password",
*     tags={"Auth"},
*     summary="Actualizar contraseña usando token",
*     description="Permite que un usuario actualice su contraseña usando el token enviado por correo.",
*     operationId="resetPassword",
*     @OA\RequestBody(
*         required=true,
*         @OA\JsonContent(
*             required={"token","email","password","password_confirmation"},
*             @OA\Property(property="token", type="string", example="token_generado_por_correo"),
*             @OA\Property(property="email", type="string", format="email", example="usuario@mail.com"),
*             @OA\Property(property="password", type="string", format="password", example="nuevaContrasena123"),
*             @OA\Property(property="password_confirmation", type="string", format="password", example="nuevaContrasena123")
*         )
*     ),
*     @OA\Response(
 *          response=200,
 *          description="Contraseña actualizada correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(property="status", type="string", example="passwords.reset")
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Validación fallida o token inválido",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
* )
*/
public function resetPassword(){}


/**
*
*
* @OA\Post(
*     path="/api/v1/forgot-password",
*     tags={"Auth"},
*     summary="Enviar link para restablecer contraseña",
*     description="Envía un correo con el enlace de restablecimiento de contraseña al email proporcionado.",
*     operationId="sendPasswordResetLink",
*     @OA\RequestBody(
*         required=true,
*         @OA\JsonContent(
*             required={"email"},
*             @OA\Property(property="email", type="string", format="email", example="usuario@mail.com")
*         )
*     ),
*     @OA\Response(
 *          response=200,
 *          description="Enlace de restablecimiento enviado correctamente",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(property="status", type="string", example="passwords.sent")
 *                      )
 *                  )
 *              }
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Email no válido o usuario no encontrado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
* )
*/
public function forgotPassword(){}


/**
*
* @OA\Get(
*     path="/api/v1/verify-email/{id}/{hash}",
*     tags={"Auth"},
*     summary="Verificar email del usuario",
*     description="Marca el correo del usuario como verificado si el hash es correcto.",
*     operationId="verifyEmail",
*     @OA\Parameter(
*         name="id",
*         in="path",
*         required=true,
*         description="ID del usuario",
*         @OA\Schema(type="integer", example=1)
*     ),
*     @OA\Parameter(
*         name="hash",
*         in="path",
*         required=true,
*         description="Hash de verificación enviado por correo",
*         @OA\Schema(type="string", example="hash_generado_por_laravel")
*     ),
*     @OA\Response(
 *          response=302,
 *          description="Redirige al frontend con ?verified=1"
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="No autenticado",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
*     security={{"bearerAuth":{}}}
* )
*/
public function emailVerification(){}


/**
* @OA\Post(
*     path="/api/v1/register",
*     summary="Registrar un nuevo usuario",
*     description="Crea un nuevo usuario en el sistema con los datos proporcionados.",
*     operationId="registerUser",
*     tags={"Auth"},
*
*     @OA\RequestBody(
*         required=true,
*         description="Datos necesarios para el registro del usuario",
*         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
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
public function register(){}


/**
 * @OA\Post(
 *     path="/api/v1/login",
 *     summary="Inicio de sesión de usuario",
 *     description="Autentica un usuario con su correo electrónico y contraseña, devolviendo el access token y refresh token.",
 *     operationId="loginUser",
 *     tags={"Auth"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         description="Credenciales del usuario para iniciar sesión",
 *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
 *     ),
 *
 *     @OA\Response(
 *          response=200,
 *          description="Inicio de sesión exitoso",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="user_tokens",
 *                              ref="#/components/schemas/LoginResponse"
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Inicio de sesión exitoso."
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
 *          response=401,
 *          description="Credenciales incorrectas",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *
 *      @OA\Response(
 *          response=500,
 *          description="Error inesperado del servidor",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 * )
 */
public function login(){}


/**
 * @OA\Post(
 *     path="/api/v1/refresh-token",
 *     summary="Refrescar token de acceso",
 *     description="Recibe un token de actualización (refresh token) y devuelve un nuevo token de acceso válido. Tambien rota el refresh token",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"refresh_token"},
 *             @OA\Property(property="refresh_token", type="string", example="def50200fcdcb15b13e...")
 *         )
 *     ),
 *    @OA\Response(
 *          response=200,
 *          description="Tokens renovados con exito",
 *          @OA\JsonContent(
 *              allOf={
 *                  @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="data",
 *                          type="object",
 *                          @OA\Property(
 *                              property="user_tokens",
 *                              ref="#/components/schemas/LoginResponse"
 *                          )
 *                      ),
 *                      @OA\Property(
 *                          property="message",
 *                          type="string",
 *                          example="Tokens renovados."
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
 *          response=401,
 *          description="Credenciales incorrectas",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *
 *      @OA\Response(
 *          response=500,
 *          description="Error inesperado del servidor",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 * )
 */
public function refreshTokens(){}



/**
 * @OA\Post(
 *     path="/api/v1/logout",
 *     summary="Cerrar sesión",
 *     description="Cierra la sesión del usuario y revoca el refresh token proporcionado.",
 *     tags={"Auth"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="x-refresh-token",
 *         in="header",
 *         description="Refresh token asociado a la sesión",
 *         required=true,
 *         @OA\Schema(type="string", example="def50200fcdcb15b13e...")
 *     ),
 *     @OA\Response(
 *          response=204,
 *          description="Sesión cerrada exitosamente (sin contenido)"
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Error en la validación de datos",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *
 *      @OA\Response(
 *          response=401,
 *          description="Credenciales incorrectas",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      ),
 *
 *      @OA\Response(
 *          response=500,
 *          description="Error inesperado del servidor",
 *          @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *      )
 * )
 */
public function logout(){}


}
