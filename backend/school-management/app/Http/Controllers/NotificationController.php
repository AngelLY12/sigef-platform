<?php

namespace App\Http\Controllers;

use App\Core\Application\Services\Misc\NotificationsServiceFacades;
use App\Http\Requests\General\PaginationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Endpoints para gestionar notificaciones de usuarios"
 * )
 */
class NotificationController extends Controller
{

    protected NotificationsServiceFacades $service;

    public function __construct(NotificationsServiceFacades $service)
    {
        $this->service=$service;
    }

    public function index(PaginationRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $perPage = $request->integer('perPage', 15);
        $page = $request->integer('page', 1);
        $notifications = $this->service->findReadNotifications($user, $page, $perPage, $forceRefresh);
        $count = $this->service->countNotifications($user, $forceRefresh);
        return Response::success([
            'notifications' => $notifications,
            'unread_count' => $count->unread_count,
            'read_count' => $count->read_count
        ]);
    }

    public function unread(PaginationRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $perPage = $request->integer('perPage', 15);
        $page = $request->integer('page', 1);
        $notifications = $this->service->findUnreadNotifications($user, $page, $perPage, $forceRefresh);
        return Response::success([
            'notifications' => $notifications
        ]);
    }

    public function markAsRead($id = null)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($id) {
            $this->service->markAsReadNotification($user, $id);
        } else {
            $this->service->markAsReadAllNotifications($user);
        }

        return Response::success([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function destroy($id)
    {
        /** @var User $user */
        $user = Auth::user();
        $this->service->deleteNotification($user, $id);
        return  Response::success(null, null, 200);
    }
}
