<?php

namespace App\Http\Controllers;

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

    public function index()
    {
        $user = Auth::user();

        return Response::success([
            'notifications' => $user->notifications()
                ->whereNotNull('read_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20),
            'unread_count' => $user->unreadNotifications()->count(),
            'read_count' => $user->notifications()->whereNotNull('read_at')->count()
        ]);
    }

    public function unread()
    {
        $user = Auth::user();
        return Response::success([
            'notifications' => $user->unreadNotifications()
                ->orderBy('created_at', 'desc')
                ->get(),
            'count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead($id = null)
    {
        $user = Auth::user();

        if ($id) {
            $notification = $user->notifications()->where('id', $id)->first();

            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        return Response::success([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $user->notifications()->where('id', $id)->delete();
        return  Response::success(null, null, 200);
    }
}
