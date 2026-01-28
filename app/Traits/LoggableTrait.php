<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LoggableTrait
{
    public static function bootLoggableTrait()
    {
        // Log Creation
        static::created(function ($model) {
            static::logToAudit('CREATE', $model, $model->toArray());
        });

        // Log Deletion
        static::deleted(function ($model) {
            static::logToAudit('DELETE', $model, $model->toArray());
        });

        // Log Update is optional/noisy, adhering to user request for "Update" if needed, 
        // but user specifically asked for "penambahan data atau penghapusan data" (Add/Delete).
        // If "keterangan (update/deleted/login)" implies Update is needed, uncomment below:

        static::updated(function ($model) {
            $changes = $model->getChanges();
            static::logToAudit('UPDATE', $model, $changes);
        });

    }

    protected static function logToAudit($action, $model, $details = [])
    {
        // Avoid logging if running in console (unless specific need) or no user
        // But for "Global Access" tracking, even system actions might be relevant.
        // We focus on User actions as requested.

        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System/Guest',
            'role' => $user ? $user->role : 'guest',
            'ip_address' => request()->ip(),
            'action' => $action,
            'model' => class_basename($model),
            'details' => $details,
        ]);
    }
}
