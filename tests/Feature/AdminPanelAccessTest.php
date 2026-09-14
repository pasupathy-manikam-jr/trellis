<?php

use App\Enums\UserRole;
use App\Models\User;

test('guests are redirected to the panel login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('students cannot reach the admin panel', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($student)->get('/admin')->assertForbidden();
});

test('instructors cannot reach the admin panel', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);

    $this->actingAs($instructor)->get('/admin')->assertForbidden();
});

test('admins can reach the admin panel', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

test('registering through the app never grants admin', function () {
    $this->post('/register', [
        'name' => 'Sneaky',
        'email' => 'sneaky@lms.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    $user = User::where('email', 'sneaky@lms.test')->sole();

    expect($user->role)->toBe(UserRole::Student);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
