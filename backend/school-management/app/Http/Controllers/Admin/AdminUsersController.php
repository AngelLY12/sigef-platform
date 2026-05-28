<?php

namespace App\Http\Controllers\Admin;

use App\Core\Application\Mappers\UserMapper;
use App\Core\Application\Services\Admin\AdminUsersServiceFacades;
use App\Core\Domain\Enum\User\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeUserStatusRequest;
use App\Http\Requests\Admin\RegisterUserRequest;
use App\Http\Requests\Admin\ShowUsersPaginationRequest;
use App\Http\Requests\General\ForceRefreshRequest;
use App\Http\Requests\General\ImportRequest;
use App\Http\Requests\Payments\Staff\DashboardRequest;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints para gestión administrativa (asignación e importación de usuarios)"
 * )
 */
class AdminUsersController extends Controller
{
    private AdminUsersServiceFacades $service;

    public function __construct(AdminUsersServiceFacades $service)
    {
        $this->service= $service;
    }

    public function registerUser(RegisterUserRequest $request)
    {
        $data = $request->validated();
        $password= Str::random(12);
        $data['password'] = $password;
        $createUser = UserMapper::toCreateUserDTO($data);

        $user = $this->service->registerUser($createUser, $password);

        return Response::success(null, 'El usuario ha sido creado con éxito.',201);

    }

    public function import(ImportRequest $request)
    {
        try {
            $file= $request->file('file')->store('imports','gcs');
        }catch (\Exception $e){
            return Response::error('No se pudo subir el archivo: ' . $e->getMessage());
        }
        $import = new UsersImport(
            $this->service,
            Auth::user(),
        );

        Excel::queueImport($import,$file,'gcs')->onQueue('imports');
        return Response::success(null, 'Usuarios procesandose, se te notificara cuando termine.');

    }

    public function index(ShowUsersPaginationRequest $request)
    {
        $forceRefresh = $request->boolean('forceRefresh');
        $perPage = $request->integer('perPage', 15);
        $page = $request->integer('page', 1);
        $status = null;
        if ($request->has('status')) {
            $status = UserStatus::tryFrom($request->validated()['status']);

            if (!$status) {
                return Response::error(
                    'Status no válido. Valores permitidos: ' .
                    implode(', ', array_column(UserStatus::cases(), 'value')),
                    422
                );
            }
        }
        $users=$this->service->showAllUsers($perPage, $page,$forceRefresh, $status);
        return Response::success(['users' => $users], 'Usuarios encontrados.');

    }

    public function getSummary(DashboardRequest $request)
    {
        $onlyThisYear = $request->validated()['only_this_year'] ?? false;
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $summary = $this->service->showUsersSummary($onlyThisYear, $forceRefresh);
        return Response::success(['summary' => $summary], 'Resumen de usuarios obtenido.');
    }

    public function getExtraUserData(ForceRefreshRequest $request, int $id)
    {
        $forceRefresh = $request->boolean('forceRefresh');
        $user = $this->service->getExtraUserData($id, $forceRefresh);
        return Response::success(['user' => $user], 'Datos extra de usuario encontrados.');
    }

    public function activateUsers(ChangeUserStatusRequest $request)
    {
        $ids = $request->validated()['ids'];
        $updated=$this->service->activateUsers($ids);

        return Response::success(['activate_users' => $updated], 'Estatus de usuarios actualizados correctamente.');

    }

    public function deleteUsers(ChangeUserStatusRequest $request)
    {
        $ids = $request->validated()['ids'];
        $updated=$this->service->deleteUsers($ids);

        return Response::success(['delete_users' => $updated], 'Estatus de usuarios actualizados correctamente.');

    }

    public function disableUsers(ChangeUserStatusRequest $request)
    {
        $ids = $request->validated()['ids'];
        $updated=$this->service->disableUsers($ids);

        return Response::success(['disable_users' => $updated], 'Estatus de usuarios actualizados correctamente.');

    }

    public function temporaryDisableUsers(ChangeUserStatusRequest $request)
    {
        $ids = $request->validated()['ids'];
        $updated=$this->service->temporaryDisableUsers($ids);

        return Response::success(['temporary_disable_users' => $updated], 'Estatus de usuarios actualizados correctamente.');

    }
}
