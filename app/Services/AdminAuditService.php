<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        User $admin,
        string $action,
        ?Model $subject,
        string $description,
        array $metadata = [],
        ?Request $request = null,
    ): AdminAuditLog {
        $request ??= request();

        return AdminAuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata !== [] ? $metadata : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
