<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function titles(AssertableInertia $page): array
{
    return collect($page->toArray()['props']['courses']['data'])->pluck('title')->all();
}

test('search matches title and summary, and ignores drafts', function () {
    Course::factory()->published()->create(['title' => 'Advanced Postgres']);
    Course::factory()->published()->create(['title' => 'Cooking', 'summary' => 'A postgres joke, honestly']);
    Course::factory()->published()->create(['title' => 'Knitting', 'summary' => 'Wool']);
    Course::factory()->create(['title' => 'Draft Postgres']);

    $this->get('/courses?q=postgres')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('courses.total', 2)
            ->where('filters.q', 'postgres')
        );
});

test('search is case-insensitive', function () {
    Course::factory()->published()->create(['title' => 'Advanced Postgres']);

    $this->get('/courses?q=POSTGRES')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('courses.total', 1));
});

test('a percent sign in the search is a literal, not a wildcard', function () {
    Course::factory()->published()->create(['title' => 'Real course']);
    Course::factory()->published()->create(['title' => 'Save 50% today']);

    $this->get('/courses?q=%25')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('courses.total', 1));
});

test('filtering by category returns only that category', function () {
    $db = Category::factory()->create(['name' => 'Databases']);
    $design = Category::factory()->create(['name' => 'Design']);

    $a = Course::factory()->published()->create(['title' => 'In databases']);
    $b = Course::factory()->published()->create(['title' => 'In design']);
    $a->categories()->attach($db);
    $b->categories()->attach($design);

    $this->get("/courses?category={$db->slug}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('courses.total', 1)
            ->where('courses.data.0.title', 'In databases')
        );
});

test('an unknown category is refused rather than silently ignored', function () {
    $this->get('/courses?category=nope')->assertSessionHasErrors('category');
});

test('price filters split free from paid', function () {
    Course::factory()->published()->create(['price_cents' => 0]);
    Course::factory()->published()->create(['price_cents' => 4900]);

    $this->get('/courses?price=free')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('courses.total', 1)
            ->where('courses.data.0.price_cents', 0));

    $this->get('/courses?price=paid')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('courses.total', 1)
            ->where('courses.data.0.price_cents', 4900));
});

test('filters combine rather than override each other', function () {
    $cat = Category::factory()->create(['name' => 'Backend']);

    $wanted = Course::factory()->published()->create(['title' => 'Queues deep dive', 'price_cents' => 0]);
    $paid = Course::factory()->published()->create(['title' => 'Queues at scale', 'price_cents' => 9900]);
    $uncategorised = Course::factory()->published()->create(['title' => 'Queues basics', 'price_cents' => 0]);

    $wanted->categories()->attach($cat);
    $paid->categories()->attach($cat);

    $this->get("/courses?q=queues&category={$cat->slug}&price=free")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('courses.total', 1)
            ->where('courses.data.0.title', 'Queues deep dive')
        );

    expect($uncategorised->title)->toBe('Queues basics');
});

test('sorting by popularity puts the most enrolled first', function () {
    $quiet = Course::factory()->published()->create(['title' => 'Quiet']);
    $busy = Course::factory()->published()->create(['title' => 'Busy']);

    Enrollment::factory()->count(3)->for($busy)->create();
    Enrollment::factory()->for($quiet)->create();

    $this->get('/courses?sort=popular')
        ->assertInertia(fn (AssertableInertia $page) => expect(titles($page)[0])->toBe('Busy'));
});

test('sorting by rating puts the best rated first', function () {
    $ok = Course::factory()->published()->create(['title' => 'Ok']);
    $great = Course::factory()->published()->create(['title' => 'Great']);

    Review::factory()->for($ok)->create(['rating' => 3]);
    Review::factory()->for($great)->create(['rating' => 5]);

    $this->get('/courses?sort=rating')
        ->assertInertia(fn (AssertableInertia $page) => expect(titles($page)[0])->toBe('Great'));
});

test('the catalogue paginates instead of returning everything', function () {
    Course::factory()->count(15)->published()->create();

    $this->get('/courses')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('courses.total', 15)
            ->where('courses.last_page', 2)
            ->count('courses.data', 12)
        );

    $this->get('/courses?page=2')
        ->assertInertia(fn (AssertableInertia $page) => $page->count('courses.data', 3));
});

test('filters survive paging', function () {
    Course::factory()->count(15)->published()->create(['price_cents' => 0]);
    Course::factory()->count(5)->published()->create(['price_cents' => 5000]);

    $this->get('/courses?price=free&page=2')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('courses.total', 15)
            ->count('courses.data', 3)
        );
});

test('category counts ignore drafts', function () {
    $cat = Category::factory()->create(['name' => 'Ops']);
    Course::factory()->published()->create()->categories()->attach($cat);
    Course::factory()->create()->categories()->attach($cat);

    $this->get('/courses')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('categories.0.courses_count', 1));
});

test('deleting a category leaves its courses alone', function () {
    $admin = User::factory()->create(['role' => App\Enums\UserRole::Admin]);
    $cat = Category::factory()->create(['name' => 'Temporary']);
    $course = Course::factory()->published()->create();
    $course->categories()->attach($cat);

    $this->actingAs($admin)->delete("/admin/categories/{$cat->slug}")->assertRedirect();

    expect(Course::whereKey($course->id)->exists())->toBeTrue()
        ->and($course->fresh()->categories)->toHaveCount(0);
});

test('only admins manage categories', function () {
    $this->actingAs(User::factory()->create())->get('/admin/categories')->assertForbidden();
    $this->actingAs(User::factory()->create())->post('/admin/categories', ['name' => 'X'])->assertForbidden();
});
