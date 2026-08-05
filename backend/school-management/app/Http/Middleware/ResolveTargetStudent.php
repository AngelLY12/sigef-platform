<?php

namespace App\Http\Middleware;

use App\Core\Domain\Enum\User\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use \Illuminate\Support\Facades\Response as IlluminateResponse;


class ResolveTargetStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $studentId = $request->header('X-Student-Id');

        if ($studentId !== null) {

            if (!is_numeric($studentId)) {
                return IlluminateResponse::error(
                    'El identificador del estudiante no es válido',
                    422
                );
            }

            if (!$user->hasRole(UserRoles::PARENT->value)) {
                return IlluminateResponse::error('No tiene permiso para seleccionar estudiantes', 403);
            }
            $targetUser = $user->resolveTargetUser((int)$studentId);

            if (!$targetUser) {
                return IlluminateResponse::error('Acceso no permitido', 403);
            }
            $request->attributes->set('targetUser', $targetUser);

        }
        return $next($request);
    }
}
