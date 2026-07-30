<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * Display a listing of the system-wide audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with(['user.business', 'admin'])->orderBy('timestamp', 'desc');

        // Optional filtering by role or action
        if ($request->filled('actor')) {
            if ($request->actor === 'admin') {
                $query->whereNotNull('admin_id');
            } elseif ($request->actor === 'user') {
                $query->whereNotNull('user_id');
            }
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
