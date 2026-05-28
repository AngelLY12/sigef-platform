<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EmailVerificationNotificationController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Response::success(null, 'Tu correo ya está verificado.',200);

        }

        $request->user()->sendEmailVerificationNotification();

        return Response::success(null,
            'Se ha enviado un nuevo enlace de verificación a tu correo electrónico.',
            200);
    }
}
