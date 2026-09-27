<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $action, ?string $description = null): ?ActivityLog
    {
        try {
            $user = Auth::user();

            return ActivityLog::create([
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'Sistem / Pengunjung Publik',
                'user_role' => $user ? $user->role : 'guest',
                'action' => $action,
                'description' => $description,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }
}
