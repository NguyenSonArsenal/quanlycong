<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Kiểm tra user có permission yêu cầu không.
     * Sử dụng trong route: ->middleware('permission:lock_day')
     * Nhiều permission: ->middleware('permission:lock_day,lock_month')  (OR logic)
     */
    public function handle(Request $request, Closure $next, ...$permissions): mixed
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }


        // Admin luôn pass
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check OR: có bất kỳ permission nào là pass
        foreach ($permissions as $perm) {
            if ($user->can($perm)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Không có quyền thực hiện thao tác này.'], 403);
        }

        return back()->with('error', '❌ Bạn không có quyền thực hiện thao tác này.');
    }
}
