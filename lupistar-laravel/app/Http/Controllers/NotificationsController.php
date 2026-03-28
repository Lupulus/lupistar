<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    public function unreadCount(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if (! $userId) {
            return response()->json(['success' => true, 'count' => 0]);
        }

        $count = Notification::query()
            ->where('user_id', $userId)
            ->where('lu', false)
            ->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function list(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => true, 'notifications' => []]);
        }

        $items = DB::table('notifications')
            ->where('user_id', $userId)
            ->orderByDesc('date_creation')
            ->limit(100)
            ->get(['id', 'titre', 'message', 'type', 'lu', 'date_creation']);

        return response()->json(['success' => true, 'notifications' => $items]);
    }

    public function markRead(Request $request, int $id)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false], 401);
        }
        DB::table('notifications')->where('id', $id)->where('user_id', $userId)->update(['lu' => true]);

        return response()->json(['success' => true]);
    }

    public function delete(Request $request, int $id)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false], 401);
        }
        DB::table('notifications')->where('id', $id)->where('user_id', $userId)->delete();

        return response()->json(['success' => true]);
    }
}
