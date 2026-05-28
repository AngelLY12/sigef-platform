<?php

namespace App\Http\Controllers\Parents;

use App\Core\Application\Services\Parents\ParentsServiceFacades;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\General\ForceRefreshRequest;
use App\Http\Requests\Parents\AcceptInviteRequest;
use App\Http\Requests\Parents\SendInviteRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Parents",
 *     description="Endpoints para invitar y aceptar padres de alumnos"
 * )
 */
class ParentsController extends Controller
{
    public function __construct(
        private ParentsServiceFacades $parentsFacade
    ) {}

    public function sendInvitation(SendInviteRequest $request)
    {
        $user=Auth::user();
        $invite = $this->parentsFacade->sendInvitation(
            studentId: $request->validated()['student_id'],
            parentEmail: $request->validated()['parent_email'],
            createdBy: $user->id
        );

        return Response::success([
            'token' => $invite->token,
            'expires_at' => $invite->expiresAt,
        ], 'Invitation enviada con éxito', 201);
    }

    public function acceptInvitation(AcceptInviteRequest $request)
    {
        $this->parentsFacade->acceptInvitation(
            token: $request->validated()['token'],
            relationship: $request->validated()['relationship'] ?? null,
        );

        return Response::success(null, 'La invitación ha sido aceptada', 200);
    }


    public function getParetChildren(ForceRefreshRequest $request)
    {
        /** @var User $user */
        $user=Auth::user();
        $user->load('roles');
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $childrenData=$this->parentsFacade->getParentChildren(UserMapper::toDomain($user), $forceRefresh);
        return Response::success([
            'children' => $childrenData
        ], 'Datos obtenidos', 200);
    }

    public function getStudentParents(ForceRefreshRequest $request)
    {
        /**@var User $user*/
        $user=Auth::user();
        $user->load('roles');
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $studentParentsData=$this->parentsFacade->getStudentParents(UserMapper::toDomain($user), $forceRefresh);
        return Response::success(['parents' => $studentParentsData], 'Datos obtenidos', 200);
    }

    public function delete(int $parentId)
    {
        /** @var User $user */
        $user=Auth::user();
        $this->parentsFacade->deleteParentStudentRelation($parentId, $user->id);
        return Response::success(null, 'Relación eliminada correctamente', 200);

    }

}
