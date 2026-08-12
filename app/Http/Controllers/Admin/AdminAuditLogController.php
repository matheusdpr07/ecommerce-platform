<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('accessAdminPanel', User::class);

        $search = $request->string('search')->trim()->toString();
        $action = $request->string('action')->trim()->toString();
        $dateFrom = $this->normalizeDate($request->string('date_from')->toString());
        $dateTo = $this->normalizeDate($request->string('date_to')->toString());
        $availableActions = AdminAuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();

        if ($action !== '' && ! in_array($action, $availableActions, true)) {
            $action = '';
        }

        $logs = AdminAuditLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AdminAuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'subject_type' => $log->auditable_type === null
                    ? null
                    : class_basename($log->auditable_type),
                'subject_id' => $log->auditable_id,
                'user' => $log->user === null ? null : [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ],
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Activity/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'action' => $action,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'actions' => $availableActions,
        ]);
    }

    private function normalizeDate(string $date): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
            return '';
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $date : '';
    }
}
