<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('notifications.view', Organization::class);

        $organization = $request->get('current_organization');
        $user = $request->user();

        $query = Notification::where('user_id', $user->id)
            ->where(function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                  ->orWhereNull('organization_id');
            })
            ->orderByDesc('created_at');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        if ($cursor = $request->query('cursor')) {
            $query->where('created_at', '<', $cursor);
        }

        $notifications = $query->limit(20)->get();

        $nextCursor = $notifications->isNotEmpty()
            ? $notifications->last()->created_at->toIso8601String()
            : null;

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title_key' => $notification->title_key,
                    'title_params' => $notification->title_params,
                    'body_key' => $notification->body_key,
                    'body_params' => $notification->body_params,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            }),
            'next_cursor' => $nextCursor,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->authorize('notifications.view', Organization::class);

        $organization = $request->get('current_organization');
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->where(function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                  ->orWhereNull('organization_id');
            })
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->authorize('notifications.view', Organization::class);

        $organization = $request->get('current_organization');
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->where(function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                  ->orWhereNull('organization_id');
            })
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}