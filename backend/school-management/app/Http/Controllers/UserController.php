<?php

namespace App\Http\Controllers;

use App\Core\Application\Services\User\UserServiceFacades;
use App\Http\Requests\General\ForceRefreshRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Operaciones para actualizar usuarios"
 * )
 */
class UserController extends Controller
{
    private UserServiceFacades $service;
    public function __construct(UserServiceFacades $service)
    {
        $this->service=$service;
    }

    public function findUser(ForceRefreshRequest $request)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $user=$this->service->findUser($forceRefresh);
        return Response::success(['user' => $user], 'Usuario encontrado.');

    }

    public function findStudentDetails(ForceRefreshRequest $request)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $userId = Auth::id();
        $user=$this->service->findStudentDetails($userId,$forceRefresh);
        return Response::success(['student_details' => $user], 'Detalles encontrados.');

    }

    public function update(UpdateUserRequest $request)
    {
        $userId=Auth::id();
        $data=$request->validated();
        $updated=$this->service->updateUser($userId,$data);

        return Response::success(['user' => $updated], 'El usuario ha sido actualizado con éxito.');

    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $data = $request->validated();
        $userId=Auth::id();
        $updated = $this->service->updatePassword(
            $userId,
            $data['currentPassword'],
            $data['newPassword']
        );

        return Response::success(['user'=>$updated], 'Password actualizada con éxito');

    }
}
