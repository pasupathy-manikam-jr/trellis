# Progress

Status log. Update at the end of each work session. Newest notes at the bottom of a phase.

**Now:** Phase 0 — not started.

---

## Phase 0 — Scaffold
- [ ] `laravel new` w/ react-starter-kit
- [ ] Postgres running locally, DB created, first migration runs
- [ ] Filament 4 installed, admin login works
- [ ] Pest + Pint green
- [ ] `storage:link` + sample video drops into `storage/app/public/videos`

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

### 2026-09-14
**Local-only constraint added.** No paid services, no accounts, works offline.
Dropped: Cloudflare/Bunny→local mp4 behind an auth route, S3→local disk,
Stripe→direct enroll (orders table still filled, gateway slots in at `OrderController::store`),
deploy target→N/A. Phase 3 renamed. Postgres kept (free locally, matches prod).

Scouted the ecosystem. No battle-tested Laravel LMS exists (best is ulearn @ 698★ on
Laravel 5.8, abandoned). Decision: build clean, borrow Moodle's schema shape and Canvas's
API naming. Scope locked to course-creator-selling; SCORM/LTI/xAPI cut. See PLAN.md.
