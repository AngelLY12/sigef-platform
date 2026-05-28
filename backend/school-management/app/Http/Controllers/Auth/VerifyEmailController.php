<?php

namespace App\Http\Controllers\Auth;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;


class VerifyEmailController extends Controller
{

    public function __invoke(Request $request): JsonResponse
    {

        if (!$request->hasValidSignature()) {
            return Response::error('Enlace de verificación inválido o expirado.', 403);
        }

        $user = \App\Models\User::find($request->route('id'));

        if (!$user) {
            return Response::error('Usuario no encontrado.', 404, null, ErrorCode::NOT_FOUND);
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->email))) {
            return Response::error('Enlace de verificación inválido.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return Response::success(['verified' => true],'Tu correo electrónico ya estaba verificado.', 200);

        }
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            return Response::success(
                [
                    'verified' => true,
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name
                    ]
                ],
                '¡Correo electrónico verificado exitosamente! Ya puedes iniciar sesión.',
                200
            );

        }

        return Response::error('No se pudo verificar el correo electrónico. Por favor, intenta nuevamente.', 500);

    }
}
