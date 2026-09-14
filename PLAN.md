# LMS — Plan

**Shape:** course creator selling online. Public catalog → purchase → learn → certificate.
**Stack:** Laravel 13 · Inertia 2 + React 19 + TS · Tailwind + shadcn/ui · Postgres 17.
**One UI stack: React everywhere, admin included.** No Blade/Livewire admin panel.
**Constraint: runs entirely on localhost. Zero paid services, zero accounts, works offline.**

## Non-goals (explicit — do not build)

SCORM · LTI 1.3 · xAPI/LRS · OneRoster/SIS sync · weighted gradebooks · live video/webinars ·
multi-tenancy · mobile app · forums · AI anything.

These are what make Moodle heavy. A creator selling courses needs none of them.
Revisit only when a paying customer names one.

## Decisions already made

| Thing | Choice | Why |
|---|---|---|
| Scaffold | `laravel/react-starter-kit` | Official. Inertia+React+TS+shadcn+auth, day one. |
| Admin | React/Inertia pages under `/admin`, gated by a `role` middleware | Keeps the app one stack. Costs real CRUD work in Phases 1 and 4 — accepted deliberately. |
| Database | **Postgres 17** | Free locally (Postgres.app / `brew install postgresql@17` / Docker). Same engine local and prod — no dialect surprises. |
| Serve | `php artisan serve` or Herd | Nothing to install, nothing to pay. |
| Video | **local disk + `<video>`** | `storage/app/public/videos`. Free, offline, good enough. |
| Files | local `public` disk | Skip S3/medialibrary entirely until there's a server. |
| Payments | **none — direct enroll** | `orders` table exists and is filled; no gateway called. Stripe drops in later. |
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

**Drip:** `lessons.drip_days` = days after `enrollments.started_at` before unlock.
One integer. No cron, no scheduler — compute on read.

**Video:** `lessons.video_path` (local disk) replaces `video_id`. Served by Laravel through an
auth-checked route, not a public URL — otherwise enrollment gating is decorative.
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
