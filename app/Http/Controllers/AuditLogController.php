<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // 1. Access Control
        $user = Auth::user();
        if (!in_array($user->role, ['direktur', 'mr'])) {
            abort(403);
        }

        // 2. Query Builder
        $query = AuditLog::query()->orderByDesc('created_at');

        // 3. Filtering
        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        if ($desc = $request->input('action')) {
            $query->where('action', $desc); // LOGIN, CREATE, DELETE
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'LIKE', "%$search%")
                    ->orWhere('ip_address', 'LIKE', "%$search%")
                    ->orWhere('model', 'LIKE', "%$search%");
            });
        }

        // 4. Pagination
        $logs = $query->paginate(20)->withQueryString();

        return view('audit_logs.index', compact('logs'));
    }
}
