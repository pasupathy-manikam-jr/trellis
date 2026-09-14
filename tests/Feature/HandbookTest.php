<?php

use App\Models\User;

test('the handbook is readable without an account', function () {
    $this->get('/handbook')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('handbook'));
});

test('the handbook is readable when signed in', function () {
    $this->actingAs(User::factory()->create())->get('/handbook')->assertOk();
});

test('the handbook carries no credentials', function () {
    // It documents the app for whoever opens it; the seeded logins belong in
    // the README, not on a page anyone can load.
    $body = $this->get('/handbook')->getContent();

    expect($body)->not->toContain('admin@lms.test')
        ->and($body)->not->toContain('student@lms.test')
        ->and(strtolower($body))->not->toContain('password&quot;:&quot;password');
});
