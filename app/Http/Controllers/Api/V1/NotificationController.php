<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return response()->json($notifications->through(fn (DatabaseNotification $notification) => [
            'id' => $notification->id,
            'type' => class_basename($notification->type),
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
        ]));
    }

    public function update(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
