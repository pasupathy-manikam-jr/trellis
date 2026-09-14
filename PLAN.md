# Trellis — Plan

**Shape:** course creator selling online. Public catalog → purchase → learn → certificate.
**Stack:** Laravel 13 · Inertia 2 + React 19 + TS · Tailwind + shadcn/ui · Postgres 17.
**One UI stack: React everywhere, admin included.** No Blade/Livewire admin panel.
**Constraint: runs entirely on localhost. Zero paid services, zero accounts, works offline.**

## Non-goals (explicit — do not build)

SCORM · LTI 1.3 · xAPI/LRS · OneRoster/SIS sync · live video/webinars ·
multi-tenancy · mobile app · AI anything.

These are what make Moodle heavy. Revisit only when someone names one.

**Two have since been built, deliberately.** The list above is a decision, not a
law, and the decision changed:

- **Weighted gradebook** — built. Assignments made grading real work rather
  than quiz percentages, and once there were two kinds of graded activity a
  single course grade had to exist. See "Grading" below.
- **Forums** — built as per-lesson Q&A rather than standalone forums. Threads
  are one level deep and attached to the lesson they are about, which is the
  part that helps a learner; a general discussion board is not.

## Decisions already made

| Thing | Choice | Why |
|---|---|---|
| Scaffold | `laravel/react-starter-kit` | Official. Inertia+React+TS+shadcn+auth, day one. |
| Admin | React/Inertia pages under `/admin`, gated by a `role` middleware | Keeps the app one stack. Costs real CRUD work in Phases 1 and 4 — accepted deliberately. |
| Database | **Postgres 17** | Free locally (Postgres.app / `brew install postgresql@17` / Docker). Same engine local and prod — no dialect surprises. |
| Serve | `php artisan serve` or Herd | Nothing to install, nothing to pay. |
| Video | **private disk + `<video>`** | `storage/app/private/videos`, streamed through an access-checked route. Never `storage/app/public` — that is symlinked into `public/` and bypasses every check. |
| Files | local `public` disk | Skip S3/medialibrary entirely until there's a server. |
| Payments | **none — direct purchase** | `orders` filled for real, coupons redeemed, no gateway called. Stripe drops into `OrderController@store`. |
| Email | Mailpit, else `MAIL_MAILER=log` | Free, local, no account. |
| Roles | `role` enum column on users | 3 roles (admin/instructor/student). Spatie permissions is overkill until it isn't. |
| PDF certs | `barryvdh/laravel-dompdf` | Pure PHP, renders offline. |

Everything above is a composer package, a Homebrew formula, or a file on disk. No API keys anywhere in `.env`.

## Schema

Trimmed from Moodle's model. Naming follows Canvas where it differs.

```
users              + role, bio, avatar
courses            slug, title, summary, description, thumbnail, price_cents, currency,
                   status(draft|published|archived), instructor_id, published_at
sections           course_id, title, position
lessons            section_id, title, slug, type(video|text|quiz|download),
                   content, video_id, duration_sec, position, is_preview, drip_days
enrollments        user_id, course_id, source(purchase|manual|free), started_at,
                   expires_at, completed_at       -- unique(user_id, course_id)
lesson_completions user_id, lesson_id, completed_at, seconds_watched
orders             user_id, course_id, stripe_id, amount_cents, coupon_id, status
coupons            code, percent_off | amount_off, max_redemptions, expires_at
quizzes            lesson_id, pass_percent, max_attempts, shuffle
questions          quiz_id, type(single|multi|truefalse), prompt, points, position
options            question_id, text, is_correct, position
quiz_attempts      user_id, quiz_id, started_at, submitted_at, score_percent, passed
attempt_answers    attempt_id, question_id, option_ids(json), is_correct
certificates       user_id, course_id, serial, issued_at
reviews            user_id, course_id, rating, body    -- unique(user_id, course_id)
```

**Progress is derived, not stored.** `completed lessons / total lessons`, cached on
`enrollments.progress_percent` only if the query ever shows up slow. Don't pre-optimize.

## Question bank

Questions belong to a **course**, not to the quiz that happened to need them
first. A quiz points at the ones it wants through a slot table.

```
question_categories  course_id, name, position
questions            course_id, question_category_id?, quiz_id?, type, prompt,
                     points, position          -- quiz_id is now only provenance
quiz_questions       quiz_id, question_id, position
                     -- unique(quiz_id, question_id)
```

**Order is a property of the slot, not the question.** The same question can sit
third in a practice quiz and first in the final. Reordering one leaves the other
untouched.

**Removing is not deleting.** Taking a question out of a quiz detaches the slot
and leaves it in the bank for reuse. Deleting from the bank cascades and removes
it from every quiz at once — two different buttons, deliberately.

Writing a question inside the quiz builder puts it in the bank automatically, so
the bank fills up as a by-product of ordinary work rather than needing to be
curated first.

**Not built:** random selection from a category per attempt, and QTI import or
export. The category table exists and is unused pending a bank UI.

## Grading

Modelled on Moodle's `grade_items` / `grade_grades`, minus the category tree.

```
assignments   lesson_id, instructions, points, due_days, allow_file
submissions   assignment_id, user_id, body, file_path, file_name, submitted_at
              -- unique(assignment_id, user_id): one piece of work per learner
grade_items   course_id, lesson_id?, name, source(quiz|assignment|manual),
              max_points, weight, position  -- unique(course_id, lesson_id)
grade_grades  grade_item_id, user_id, points, feedback, graded_at, graded_by
              -- unique(grade_item_id, user_id)
```

**A graded activity owns its column.** Saving a quiz or assignment syncs a
`grade_item`, so a renamed lesson or changed points total cannot leave a stale
column behind. `source = manual` is a column an instructor keeps by hand for
work done off the platform; only those can be deleted.

**Quizzes are scored out of 100, not their own points total.** A quiz's points
move every time a question is added, and a column that rescales itself
underneath marks already given is worse than one fixed denominator. The
gradebook keeps the learner's *best* attempt.

**The course grade is a weighted average over columns marked so far.** Weights
are relative and normalised against the course total, so they never have to add
up to anything. Unmarked work is left out rather than counted as zero —
otherwise every learner reads 0% until the last thing is graded. No marks at
all means no grade, not zero.

**Submitting completes the lesson; the mark is separate.** Handing work in is
doing the work. Whether it was any good is the gradebook's business, not the
progress bar's. Neither quiz nor assignment lessons can be ticked complete by
hand.

**Drip:** `lessons.drip_days` = days after `enrollments.started_at` before unlock.
One integer. No cron, no scheduler — compute on read.

**Video:** `lessons.video_path` points at the **private** disk (`storage/app/private/videos`).
Served through `LessonVideoController`, which applies the same `LessonPolicy@view` as the
lesson page. `storage/app/public` is symlinked into `public/` — anything there is fetchable
with no session at all, which would make the whole gate decorative.
`// ponytail: single-file mp4, no ABR/no signing. Swap for Bunny/Cloudflare Stream at deploy.`

## Phases

Each phase ships something usable. Stop after any phase and you still have a product.

### Phase 0 — Scaffold
Laravel 13 + react-starter-kit, Postgres, Pest + Pint. Runs on `php artisan serve`.

### Phase 1 — Content model + admin
`courses / sections / lessons` migrations + models. `/admin` route group behind an
`EnsureUserIsAdmin` middleware. React admin pages: course list, course editor with nested
sections + lessons (drag-reorder). Public catalog page + course detail page. No enrollment yet.
**Done when:** an instructor can build a full course in admin and see it on the public site.

### Phase 2 — Enrollment + player
`enrollments`, `lesson_completions`. Learner course player: sidebar outline, video/text
lesson view, mark-complete, progress bar, next/prev. Manual enrollment from admin only.
**Done when:** a manually-enrolled student can watch a course start to finish and hit 100%.

### Phase 3 — Orders + coupons (no gateway)
`orders`, `coupons`. "Enroll" button → create order (`status=paid`, `amount_cents` from
course price) → create enrollment. Coupon codes apply and are redeemed for real.
Free courses take the same path at 0.
**Done when:** a visitor can enroll via the order flow, coupon math is correct, and the
order history is right. The only missing piece is a card charge.

`// ponytail: no payment gateway. Cashier + Stripe Checkout slots in at
// OrderController::store when you go live — everything downstream already works.`

### Phase 4 — Quizzes + certificates
Quiz builder in the React admin. Attempt flow, grading, pass/fail, attempt limits.
Certificate issued on course completion (+ passing all required quizzes). PDF + public
verify URL `/verify/{serial}`.
**Done when:** completing a course with a quiz produces a downloadable, verifiable cert.

### Phase 5 — Drip + polish
`drip_days` unlock logic. Transactional email (welcome, purchase, completion).
Reviews. Instructor dashboard (revenue, enrollments, completion rate).

## Risks

- **Video files in the repo** — `storage/` is gitignored, keep it that way. Use 2-3 small sample clips for dev; don't commit a gigabyte.
- **Gated video must not be a public URL** — stream through a controller that checks enrollment. Easy to get wrong on a local disk, and it's the whole access model.
- **Content edits mid-course** — deleting a lesson orphans completions. Soft-delete lessons, never hard-delete published ones.
- **Hand-built admin CRUD** — no admin-panel package means Phases 1 and 4 carry the course builder and quiz builder themselves. This is the price of one UI stack; budget for it rather than rediscovering it mid-phase.
- **`role` is mass-assignable** — it has to be, for seeders and factories. The guard is a test asserting a registration POST carrying `role: admin` still yields a student. Keep that test.

## Proof

Pest feature tests per phase, on the paths where money and access live:
enrollment gating, video route auth, drip unlock, coupon math, quiz scoring, certificate issuance.
Nothing else gets a test unless it breaks twice.
