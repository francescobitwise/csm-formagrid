<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\StaffAuditLog;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StaffAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = StaffAuditLog::query()
            ->with('user')
            ->orderByDesc('created_at');

        $userId = $request->query('user_id');
        if (is_string($userId) && $userId !== '') {
            $query->where('user_id', $userId);
        }

        $logs = $query->paginate(40)->withQueryString();

        $staffUsers = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Instructor])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('tenant.admin.audit-log.index', [
            'logs' => $logs,
            'staffUsers' => $staffUsers,
            'filterUserId' => is_string($userId) ? $userId : '',
        ]);
    }
}
