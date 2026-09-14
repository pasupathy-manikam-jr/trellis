<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * Admins and instructors both reach the course workspace. Which courses
     * they may touch once inside is CoursePolicy's job, not this gate's.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            in_array($request->user()?->role, [UserRole::Admin, UserRole::Instructor], true),
            403,
        );

        return $next($request);
    }
}
