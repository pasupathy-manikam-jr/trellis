<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;

// Native browser validation is switched off, so the server is the only thing
// standing between an empty form and the database.

test('login rejects an empty submission with field errors', function () {
    $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
});

test('login rejects a malformed email', function () {
    $this->post('/login', ['email' => 'not-an-email', 'password' => 'x'])
        ->assertSessionHasErrors('email');
});

test('registration rejects an empty submission', function () {
    $this->post('/register', [])->assertSessionHasErrors(['name', 'email', 'password']);

    expect(User::count())->toBe(0);
});

test('registration rejects a mismatched confirmation', function () {
    $this->post('/register', [
        'name' => 'A',
        'email' => 'a@lms.test',
        'password' => 'password',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
});

test('a blank section title is refused', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $section = Section::factory()->create(['title' => 'Kept']);

    $this->actingAs($admin)
        ->patch("/admin/sections/{$section->id}", ['title' => ''])
        ->assertSessionHasErrors('title');

    expect($section->fresh()->title)->toBe('Kept');
});

test('numeric bounds are enforced server-side, not by the input', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course = Course::factory()->create();

    // A negative price can now reach the server; it must still be refused.
    $this->actingAs($admin)
        ->patch("/admin/courses/{$course->slug}", [
            'title' => $course->title,
            'price_cents' => -500,
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('price_cents');

    $this->actingAs($admin)
        ->post('/admin/coupons', ['code' => 'OVER', 'kind' => 'percent', 'percent_off' => 150])
        ->assertSessionHasErrors('percent_off');
});
