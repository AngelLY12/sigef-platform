<?php

namespace App\Http\Controllers;

use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Application\Mappers\UserMapper;
use App\Core\Application\Services\Auth\LoginServiceFacades;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints relacionados con la autenticación de usuarios, tokens de acceso, contraseñas y verificación"
 * )
 */
class LoginController extends Controller
{

    protected LoginServiceFacades $loginService;

    public function __construct(LoginServiceFacades $loginService)
    {
        $this->loginService=$loginService;
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $createUser = UserMapper::toCreateUserDTO($data);

        $user = $this->loginService->register($createUser);

        return Response::success(null, 'El usuario ha sido creado con éxito.', 201);


    }

    public function login(LoginRequest $request){

        $request->authenticate();
        $data = $request->validated();
        $loginRequest = GeneralMapper::toLoginDTO($data);

        $userToken = $this->loginService->login($loginRequest);

        return Response::success(['user_tokens' => $userToken], 'Inicio de sesión exitoso.');

   }
}
