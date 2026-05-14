<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InternalApiOnly
{
    /**
     * Chỉ cho phép API được gọi từ chính domain của site.
     * Block mọi request đến từ origin/referer khác.
     */
    public function handle(Request $request, Closure $next)
    {
        // Bỏ qua kiểm tra ở môi trường local (development)
        if (app()->environment('local')) {
            return $next($request);
        }

        $allowedHost = parse_url(config('app.url'), PHP_URL_HOST);
        // Cũng cho phép chính host của server (trường hợp APP_URL chưa set đúng)
        $serverHost  = $request->getHost();

        // Lấy origin hoặc referer từ request
        $origin  = $request->header('Origin');
        $referer = $request->header('Referer');

        // Xác định host gửi request
        $requestHost = null;
        if ($origin) {
            $requestHost = parse_url($origin, PHP_URL_HOST);
        } elseif ($referer) {
            $requestHost = parse_url($referer, PHP_URL_HOST);
        }

        // Strip www. để so sánh linh hoạt hơn
        $normalize = fn($h) => preg_replace('/^www\./', '', strtolower($h ?? ''));

        $isSameHost = $requestHost && (
            $normalize($requestHost) === $normalize($allowedHost) ||
            $normalize($requestHost) === $normalize($serverHost)
        );
        $isServerSide = !$origin && !$referer; // cron job, artisan, internal call

        if ($isSameHost || $isServerSide) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized.',
        ], 403);
    }
}
