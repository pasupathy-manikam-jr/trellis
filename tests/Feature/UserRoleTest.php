<?php

use App\Enums\UserRole;
use App\Models\User;

test('registering through the app never grants a privileged role', function () {
    $this->post('/register', [
        'name' => 'Sneaky',
        'email' => 'sneaky@lms.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    expect(User::where('email', 'sneaky@lms.test')->sole()->role)->toBe(UserRole::Student);
});

test('role round-trips through the database as an enum', function () {
    foreach (UserRole::cases() as $role) {
        expect(User::factory()->create(['role' => $role])->fresh()->role)->toBe($role);
    }
});
