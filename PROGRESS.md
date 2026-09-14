# Progress

Status log. Update at the end of each work session. Newest notes at the bottom of a phase.

**Now:** Phase 0 ✅ complete. Next: Phase 1 — content model + admin.

---

## Phase 0 — Scaffold
- [x] `laravel new` w/ react-starter-kit — **Laravel 13.31.0**, React 19, Inertia 2, TS, shadcn
- [x] Postgres 17.11 (Postgres.app) running; `lms` + `lms_test` created, migrations run
- [x] **Filament 5.8.1** at `/admin`, gated on `UserRole::Admin` (5 access tests)
- [x] **Pest 5** replaces PHPUnit; 31 passed. Pint clean.
- [x] `storage:link` + `storage/app/public/videos` (gitignored, `.gitkeep` only)
- [x] Committed `fa69c08`, upgraded in `ff83977`

## Phase 1 — Content model + admin
- [ ] Migrations: courses, sections, lessons
- [ ] Models + relations + factories
- [ ] Filament: CourseResource (w/ nested sections + lessons, reorderable)
- [ ] Public catalog `/courses`
- [ ] Public course detail `/courses/{slug}` (outline, preview lessons)
- [ ] ✅ *Done when:* course built in admin renders publicly

## Phase 2 — Enrollment + player
- [ ] Migrations: enrollments, lesson_completions
- [ ] Enrollment policy / gate (`EnrollmentPolicy`)
- [ ] Player shell: outline sidebar + lesson pane
- [ ] Video lesson (local mp4 streamed through an auth-checked route)
- [ ] Text lesson
- [ ] Mark complete + progress %
- [ ] Manual enroll action in Filament
- [ ] Test: non-enrolled user gets 403 on a non-preview lesson **and on its video URL**
- [ ] ✅ *Done when:* enrolled student completes a course to 100%

## Phase 3 — Orders + coupons (no gateway)
- [ ] Migrations: orders, coupons
- [ ] Enroll action → order (`status=paid`) → enrollment, in one transaction
- [ ] Coupon apply + redemption count + expiry
- [ ] Free-course path (same flow, amount 0)
- [ ] Order history page
- [ ] Test: coupon math, expired/exhausted coupon rejected, double-enroll is a no-op
- [ ] ✅ *Done when:* visitor enrolls end to end; only the card charge is missing

## Phase 4 — Quizzes + certificates
- [ ] Migrations: quizzes, questions, options, quiz_attempts, attempt_answers
- [ ] Filament quiz builder
- [ ] Attempt flow + grading + attempt limits
- [ ] Certificates table + PDF
- [ ] Public verify `/verify/{serial}`
- [ ] Test: scoring + pass threshold + max attempts
- [ ] ✅ *Done when:* completion yields a verifiable cert

## Phase 5 — Drip + polish
- [ ] `drip_days` unlock (computed on read)
- [ ] Emails: welcome, enrollment receipt, completion (Mailpit)
- [ ] Reviews
- [ ] Instructor dashboard

---

## Log

### 2026-09-14 — upgraded to Laravel 13
Scaffolded on 12.69.2, then moved to latest: **Laravel 13.31.0 · Filament 5.8.1 · Pest 5 ·
Tinker 3 · Livewire 4** (Filament 5 pulls Livewire 4). 31 tests still green, no code changes
needed — we use very little Filament surface yet, which is why this was cheap. Doing it now
rather than after Phase 1 was the whole point.

**Held back deliberately:** Inertia stays at 2.0.27 (it supports Laravel 13). Inertia 3 is
out but would drag `@inertiajs/react` v3 and the starter kit's frontend along with it —
a separate migration with no benefit to us today.

**Found and fixed:** the starter kit ships `minimum-stability: dev`. Stable `ramsey/uuid
4.9.3` caps `brick/math` at `<=0.18`; composer took `brick/math 0.19.1`, found no stable
uuid that fit, and silently locked a `4.x-dev` git branch. Set `minimum-stability: stable`
— lock is now 100% tagged releases. Worth knowing this default is there.

### 2026-09-14 — Phase 0 done
Toolchain found: PHP 8.4.17 (MAMP, has `pdo_pgsql`), Composer 2.9, Node 24. No brew, no
Docker, no Postgres → installed Postgres.app 2.9.6 (PG 17.11), data dir at
`~/Library/Application Support/Postgres/var-17`, started via bundled `pg_ctl`.

**Two calls worth remembering:**
1. Tests run on Postgres (`lms_test`), not SQLite in-memory. The starter kit defaults to
   SQLite; keeping it would reintroduce the exact dialect risk we picked Postgres to avoid.
   Costs ~2s per suite run. Worth it.
2. `role` landed in Phase 0, not Phase 1. Filament's panel allows any authenticated user by
   default — without the gate, every registered student could reach `/admin`. `role` is in
   `$fillable`, so there's a test asserting registration can't self-assign `admin`.

Server start (not automatic on boot):
`"/Applications/Postgres.app/Contents/Versions/17/bin/pg_ctl" -D "$HOME/Library/Application Support/Postgres/var-17" start`
Or just open Postgres.app once and it adopts the data dir.

**Local-only constraint added.** No paid services, no accounts, works offline.
Dropped: Cloudflare/Bunny→local mp4 behind an auth route, S3→local disk,
Stripe→direct enroll (orders table still filled, gateway slots in at `OrderController::store`),
deploy target→N/A. Phase 3 renamed. Postgres kept (free locally, matches prod).

Scouted the ecosystem. No battle-tested Laravel LMS exists (best is ulearn @ 698★ on
Laravel 5.8, abandoned). Decision: build clean, borrow Moodle's schema shape and Canvas's
API naming. Scope locked to course-creator-selling; SCORM/LTI/xAPI cut. See PLAN.md.
