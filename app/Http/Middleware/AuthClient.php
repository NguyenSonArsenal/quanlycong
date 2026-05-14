<?php

namespace App\Http\Middleware;

use Closure;

class AuthClient
{
    public function handle($request, Closure $next)
    {
        if (clientCheck()) {
            return $next($request);
        }

        // AJAX request (cart.add, cart.update, ...) → trả JSON để JS xử lý redirect
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'unauthenticated' => true,
                'message'         => 'Vui lòng đăng nhập để tiếp tục.',
                'login_url'       => clientRoute('auth.login') . '?redirect=' . urlencode(url()->previous()),
            ], 401);
        }

        // Lưu URL intended để sau login quay lại
        // Với GET request: lưu URL hiện tại
        // Với POST request (buy_now, checkout, ...): lưu URL referrer
        $intended = $request->isMethod('GET') ? $request->url() : url()->previous();
        session(['url.intended' => $intended]);

        return redirect()->route(clientRouteName('auth.login'));
    }
}
