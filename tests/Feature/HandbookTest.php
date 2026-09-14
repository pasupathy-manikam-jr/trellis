<?php

use App\Enums\UserRole;
use App\Models\User;

test('the learner handbook is open to everyone', function () {
    $this->get('/handbook')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('handbook/learner'));
});

test('the staff handbook is for staff only', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->get('/handbook/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('handbook/admin'));

    $this->actingAs(User::factory()->create(['role' => UserRole::Instructor]))
        ->get('/handbook/admin')
        ->assertOk();
});

test('a learner cannot reach the staff handbook', function () {
    $this->actingAs(User::factory()->create())->get('/handbook/admin')->assertForbidden();
});

test('a guest is sent to log in for the staff handbook', function () {
    $this->get('/handbook/admin')->assertRedirect('/login');
});

test('neither handbook carries credentials', function () {
    // They document the app for whoever opens them; the seeded logins belong
    // in the README, not on a page anyone can load.
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    foreach ([$this->get('/handbook'), $this->actingAs($admin)->get('/handbook/admin')] as $response) {
        expect($response->getContent())
            ->not->toContain('admin@lms.test')
            ->not->toContain('student@lms.test');
    }
});
