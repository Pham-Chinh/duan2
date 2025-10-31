<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     * 
     * Note: Despite the name "IsAdmin", this middleware now allows both Admin and Editor
     * to access dashboard and content management areas.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập');
        }

        // Check if user can access dashboard (Admin or Editor)
        if (!auth()->user()->canAccessDashboard()) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập trang này');
        }

        return $next($request);
    }
}
