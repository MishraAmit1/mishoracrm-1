<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // ── All notifications page ────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Notification::query()->where('user_id', Auth::id())->latest();

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $request->status === 'unread'
                ? $query->unread()
                : $query->read();
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount   = Notification::query()->where('user_id', Auth::id())->unread()->count();
        $types         = config('notifications.types');

        return view('tenant.notifications.index', compact(
            'notifications', 'unreadCount', 'types'
        ));
    }

    // ── Get unread count + latest (for bell dropdown) ─────────────
    public function latest(): JsonResponse
    {
        $notifications = Notification::query()->where('user_id', Auth::id())
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'url'        => $n->url,
                'color'      => $n->color,
                'icon'       => $n->icon,
                'icon_svg'   => $n->icon_svg,
                'is_read'    => $n->is_read,
                'time'       => $n->created_at->diffForHumans(),
            ]);

        $unreadCount = Notification::query()->where('user_id', Auth::id())->unread()->count();

        return response()->json([
            'success'      => true,
            'notifications'=> $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // ── Mark single as read ───────────────────────────────────────
    public function markRead(int $id): JsonResponse
    {
        $notification = Notification::query()->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    // ── Mark all as read ──────────────────────────────────────────
    public function markAllRead(): JsonResponse
    {
        Notification::query()->where('user_id', Auth::id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'All marked as read.']);
    }

    // ── Delete single ─────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        Notification::query()->where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['success' => true]);
    }

    // ── Clear all read notifications ──────────────────────────────
    public function clearRead(): RedirectResponse
    {
        Notification::query()->where('user_id', Auth::id())->read()->delete();

        return back()->with('success', 'Read notifications cleared.');
    }

    // ── Preferences page ──────────────────────────────────────────
    public function preferences(): View
    {
        $prefs    = NotificationPreference::getForUser(Auth::id(), Auth::user()->tenant_id);
        $types    = config('notifications.types');
        $channels = config('notifications.channels');
        $groups   = collect($types)->groupBy(fn($t) => $t['group'] ?? 'Other', true);

        return view('tenant.notifications.preferences', compact(
            'prefs', 'types', 'channels', 'groups'
        ));
    }

    // ── Save preferences ──────────────────────────────────────────
    public function savePreferences(Request $request): RedirectResponse
    {
        $types = array_keys(config('notifications.types', []));
        $prefs = $request->input('prefs', []);

        foreach ($types as $type) {
            $typePrefs = $prefs[$type] ?? [];

            NotificationPreference::updateOrCreate(
                [
                    'tenant_id' => Auth::user()->tenant_id,
                    'user_id'   => Auth::id(),
                    'type'      => $type,
                ],
                [
                    'in_app'   => isset($typePrefs['in_app']) ? (bool) $typePrefs['in_app'] : false,
                    'email'    => isset($typePrefs['email']) ? (bool) $typePrefs['email'] : false,
                    'whatsapp' => isset($typePrefs['whatsapp']) ? (bool) $typePrefs['whatsapp'] : false,
                    'slack'    => isset($typePrefs['slack']) ? (bool) $typePrefs['slack'] : false,
                    'push'     => isset($typePrefs['push']) ? (bool) $typePrefs['push'] : false,
                ]
            );
        }

        return back()->with('success', 'Notification preferences saved.');
    }
}