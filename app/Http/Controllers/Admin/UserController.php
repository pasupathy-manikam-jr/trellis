<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/users/index', [
            'users' => User::query()
                ->withCount('courses')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    ...$user->only('id', 'name', 'email'),
                    'role' => $user->role->value,
                    'courses_count' => $user->courses_count,
                ]),
            'roles' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $role = UserRole::from($request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ])['role']);

        // Demoting yourself out of admin locks you out of this page, and there
        // may be nobody left who can undo it.
        if ($user->id === $request->user()->id && $role !== UserRole::Admin) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own admin access.',
            ]);
        }

        $user->update(['role' => $role]);

        return back()->with('success', "{$user->name} is now a {$role->value}.");
    }
}
