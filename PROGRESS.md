# Trellis — Progress

Status log. Update at the end of each work session. Newest notes at the bottom of a phase.

**Now:** paused. All five phases shipped, plus resume, catalogue, Q&A, instructor role,
stand-in checkout and the handbooks. 238 tests. Public at
https://github.com/pasupathy-manikam-jr/trellis

**Next direction: Moodle-ward.** First slice — assignments and a weighted
gradebook — is done. See the note at the top of the log for what is left.

---

## Phase 0 — Scaffold
- [x] `laravel new` w/ react-starter-kit — **Laravel 13.31.0**, React 19, Inertia 2, TS, shadcn
- [x] Postgres 17.11 (Postgres.app) running; `lms` + `lms_test` created, migrations run
- [x] `UserRole` enum + `role` column on users (admin/instructor/student)
- [x] **Pest 5** replaces PHPUnit; 31 passed. Pint clean.
- [x] `storage:link` + `storage/app/public/videos` (gitignored, `.gitkeep` only)
- [x] Committed `fa69c08` → upgraded `ff83977` → Filament removed `<pending>`

## Phase 1 — Content model + admin
- [x] Migrations: courses, sections, lessons (lessons soft-delete)
- [x] Models + relations + factories; `Orderable` and `HasUniqueSlug` concerns
- [x] `/admin` route group + `EnsureUserIsAdmin` middleware (alias `admin`)
- [x] React admin: course list, create, editor (nested sections/lessons, reorder, lesson dialog)
- [x] Public catalog `/courses`
- [x] Public course detail `/courses/{slug}` (outline, preview badges)
- [x] Seeded demo course + a draft that must stay hidden
- [x] 50 tests pass (22 new). Pint clean.
- [x] ✅ *Done when:* course built in admin renders publicly — **verified live via MAMP**

## Phase 2 — Enrollment + player
- [x] Migrations: enrollments, lesson_completions (both uniquely constrained)
- [x] `LessonPolicy@view/@complete` + `CoursePolicy@enroll/@learn` — one rule, every caller
- [x] Player: outline sidebar, progress bar, prev/next, locked lessons
- [x] Video lesson — private disk, streamed via `LessonVideoController`, Range supported
- [x] Video upload in the admin lesson dialog (spoofed PATCH, replaces + deletes the old file)
- [x] Text lesson
- [x] Mark complete / un-complete + derived progress %
- [x] Manual enrol + revoke in the React admin; free self-enrolment on the course page
- [x] Dashboard replaced with the learner's own courses
- [x] Test: non-enrolled user gets 403 on a non-preview lesson **and on its video URL**
- [x] 76 tests (26 new). Pint clean. Gating verified live through MAMP.
- [x] ✅ *Done when:* enrolled student completes a course to 100%

## Phase 3 — Orders + coupons (no gateway)
- [x] Migrations: orders, coupons
- [x] Purchase → order (`status=paid`) → coupon redemption → enrolment, one transaction
- [x] Coupon apply + redemption count + expiry + `lockForUpdate` on the limit
- [x] Free-course path (same flow, amount 0)
- [x] Order history `/orders`; admin `/admin/orders` with refund; admin `/admin/coupons`
- [x] Refund revokes access and returns the redemption
- [x] Test: coupon math, expired/exhausted rejected, double-purchase refused
- [x] 89 tests (18 new, 5 superseded removed). Pint clean.
- [x] ✅ *Done when:* visitor enrols end to end — **verified live: $49.00 → HALFOFF → $24.50**

## Phase 4 — Quizzes + certificates
- [x] Migrations: quizzes, questions, options, quiz_attempts, attempt_answers, certificates
- [x] React quiz builder (settings + questions + options, reorder, validation)
- [x] Attempt flow + grading + attempt limits + pass threshold
- [x] Certificates issued on course completion; PDF via dompdf
- [x] Public verify `/verify/{serial}`
- [x] Test: scoring, weighting, exact-match multi, tampering, limits, issuance
- [x] 115 tests (26 new). Pint + tsc clean.
- [x] ✅ *Done when:* completion yields a verifiable cert

## Phase 5 — Drip + polish
- [x] `drip_days` unlock, computed on read, enforced in `LessonPolicy`
- [x] Emails: welcome, receipt, completion (log driver; Mailpit optional)
- [x] Reviews: enrolled-only, one per learner, average on catalog and course page
- [x] Instructor dashboard at `/admin/insights` (revenue, completion rate, rating)
- [x] `window.confirm` replaced with shadcn `AlertDialog`; native date input replaced
      with a shadcn `Popover` + `Calendar` picker
- [x] 147 tests (30 new). Pint + tsc clean.

---

## Log

### 2026-09-14 — question bank
Questions were locked to one quiz, so a good question had to be rewritten for
every quiz that wanted it. They now live in a course-level bank and quizzes
point at them through `quiz_questions` slots.

The refactor kept `Quiz::questions()` returning the same ordered collection, so
grading, the player payload and every existing test carried on unchanged — all
272 stayed green through the schema move, which is what made it safe to do.

**Caught while doing it:** moving question order onto the slot meant the old
`Question::move()` would have reordered the *bank* rather than the quiz. No test
covered that, and it would have been a quiet, confusing bug. `Quiz::moveQuestion()`
now owns it, and the routes are quiz-scoped.

The bank now has a page of its own, with categories, and each question shows
which quizzes depend on it. `question_categories` is no longer a table with
nothing pointing at it.

**Still missing from Moodle's version:** random selection per attempt, and QTI.
Random is deliberately deferred rather than forgotten — grading has to happen
against the questions a learner was actually shown, so `quiz_attempts` would
need to record its own question set instead of reading the quiz's. That changes
the attempt model, which is a bigger job than it looks.

### 2026-09-14 — assignments and the gradebook
The first Moodle-ward slice. PLAN.md's non-goals list has been amended rather
than ignored: weighted gradebooks are no longer a non-goal, and the reason is
recorded there.

**Assignments** are a lesson type with a brief, a points total and an optional
deadline counted from each learner's own enrolment — the same clock drip uses.
One submission per learner, editable until it is marked, text and/or a file.
Coursework goes on the private disk and is served through a check, like lesson
video; the author and the course owner can read it and nobody else. Late work
is accepted and flagged, never refused.

**The gradebook** is `grade_items` / `grade_grades`, Moodle's shape without the
category tree. A quiz or assignment syncs its own column on save. The course
grade is a weighted average over marked columns only.

**Decisions worth not relitigating:**
- Quizzes score out of 100, not their own points total, so adding a question
  does not rescale marks already given.
- The gradebook keeps a learner's best quiz attempt, not their latest —
  retaking for practice should not cost them.
- Unmarked work is excluded from the average, and no marks means no grade
  rather than zero. Same principle as an unrated course having no average.
- Handing in completes the lesson; the mark is separate.

29 tests. Two bugs caught on the way: a policy method for Submission was
written on AssignmentPolicy, where Laravel never looks for it; and the lesson
icon maps needed the new type, which only TypeScript noticed.

### Next session — direction
The ask is to grow toward what Moodle does. That reverses part of PLAN.md,
which lists SCORM, LTI, xAPI and weighted gradebooks as explicit non-goals for
a course-selling product. Do not treat that list as settled any more; treat it
as the decision that is being revisited.

Worth knowing before picking any of it up:

- ~~**Gradebook**~~ — done, along with assignments. What is still missing from
  Moodle's version: grade *categories* (a tree, with weights at each level),
  letter/scale grades rather than points, and grade export.
- **SCORM / LTI / xAPI** are the interoperability layer and the reason Moodle
  is heavy. The Composer packages were scouted in session one and are in
  PLAN.md's history — `devianl2/laravel-scorm`, `packbackbooks/lti-1p3-tool`,
  `rusticisoftware/tincan`, `trax2/framework`. Do not hand-roll these.
- **Cohorts and groups** — Moodle separates enrolment from grouping. Today
  enrolment is one row per learner per course, with no notion of a cohort.
- **Assignments with submissions and marking** — the obvious gap next to
  quizzes; a `lessons.type` of `assignment` plus a submission model.
- **Course completion rules** — Moodle lets you define what completion *means*
  per course. Today it is hardcoded: every lesson done.

Still open from before, unrelated to Moodle:
- No LICENSE file; a public repo without one is all-rights-reserved.
- CI was removed to get the push through. `gh auth refresh -s workflow` first,
  then write one that matches this project (Postgres service, Pint, Pest) —
  the starter kit's ran against SQLite and would fail.
- The claude.ai handbook artifact is stale now the real one ships in-app.

### 2026-09-14 — Phase 5 done
Drip, emails, reviews, insights.

**Drip went into the policy, not the controller.** `LessonPolicy` gained one private
`isOpenToLearner()` that every learner-facing rule routes through — so a dripped lesson is
shut to the page, the video stream *and* the quiz at once. Putting the check only on the
page would have left the video URL open, which is the same mistake Phase 2 caught. Computed
from `enrollment.started_at`: no scheduler, no unlock rows to drift.

**Two design calls the tests forced:**
- An unrated course has `average: null`, not `0.0`. Zero renders as a *bad* rating; null
  renders as "no ratings yet".
- A course with no enrolments has `completion_rate: null`, not `0%` — nobody failed to
  finish it, nobody started.

**Mail is sent after the transaction commits**, so a rolled-back purchase never produces a
receipt for an order that does not exist. The completion mail is gated on
`wasRecentlyCreated`, so re-running the completion check does not mail twice — there is a
test for exactly that.

**UI corrections during the phase:** `window.confirm` is gone, replaced by a single
`ConfirmButton` wrapping shadcn's `AlertDialog` (themed, focus-trapped, Escape-dismissible)
used at four call sites. The native `<input type="date">` is gone, replaced by a `Popover` +
`Calendar` picker. `react-day-picker` is pinned to v9 because v10 changed the `classNames`
API that shadcn's Calendar is written against.

### 2026-09-14 — Phase 4 done
Quizzes, grading, certificates.

**Grading rules, each asserted:**
- Exact match only — a multi-select scores nothing unless the chosen set equals the correct
  set, so ticking every box scores zero rather than everything.
- Weighted by points, not question count.
- Option ids are intersected with the question's own options before comparing, so a forged
  or borrowed id cannot turn a wrong answer right. There is a test that smuggles another
  question's correct id and expects zero.
- Unanswered is wrong, not skipped. Duplicate ids count once. Pass threshold is inclusive.

**The integration that makes completion mean something.** `LessonPolicy@complete` refuses a
quiz lesson outright — a quiz completes only by being passed. Without that, "finished the
course" would just mean "clicked ten buttons". Passing writes the completion, which may
finish the course, which issues the certificate. One chain, one place.

**The answer key never ships.** `is_correct` is stripped from the player payload; the test
asserts the rendered page does not contain the string at all.

**True/false was cut.** It is a single-choice question with two options — one less branch in
grading and in the builder.

**A certificate is not revoked** by un-ticking a lesson. It attests that the course *was*
completed; the enrolment reopens, the attestation stands.

### 2026-09-14 — Phase 3 done
Orders, coupons, refunds. Still no gateway.

**Consolidated rather than added.** Phase 2 had `EnrollmentController` granting free
enrolment; Phase 3 would have added a second path for paid. Two places that grant access is
how they drift apart. `EnrollmentController` is deleted — free and paid now run the same
`OrderController@store`, differing only in price. `CoursePolicy@enroll` became `@purchase`.
Five Phase 2 tests were superseded by CheckoutTest and removed rather than left to rot.

**Money rules worth remembering:**
- A discount is clamped to the subtotal — a coupon can take a price to zero, never below.
- Percentages round half-up, favouring the customer by at most a cent. Asserted directly.
- The coupon row is `lockForUpdate`-ed before its limit is checked, so two people racing for
  the last redemption cannot both win it.
- A discounted-to-zero order is recorded as `source = free`, not `purchase`.

**Refund revokes access.** Otherwise the money goes back and the course does not. It also
decrements the coupon's redemption count.

**The Stripe seam is one place:** `OrderController@store`, between validation and the
transaction. Everything after it — order, redemption, enrolment — already works and is
tested. No `stripe_id` column yet; that lands with the gateway and its own idempotency needs.

### 2026-09-14 — Phase 2 done
Enrolment, the player, progress, and gated video.

**The disk was the real trap.** Phase 0 created `storage/app/public/videos` because the plan
said so. That directory is symlinked into `public/` by `storage:link`, so anything in it is
downloadable with no session at all — the access check would have been decorative from the
first upload. Videos now live on the **private** disk and are streamed by
`LessonVideoController`. PLAN.md was wrong and has been corrected.

**One rule, not two.** `LessonPolicy@view` is the single gate. The player page, the lesson
payload and the video stream all call it. A guard on the page alone would have left the
video URL open — so there is a test that hits the video route directly as guest, as a
signed-in stranger, and as an enrolled learner.

**Progress stays derived**, never stored — `completed / total` counted at read time, so
editing a course cannot leave a stale percentage. `enrollments.completed_at` is the one
stamped value, and it un-stamps if a lesson is un-completed.

**Proved rather than assumed:** multipart + spoofed PATCH mangles booleans in ways that are
easy to get wrong, so there is a test posting exactly what the browser sends
(`_method=patch`, `is_preview="1"`, a fake mp4) and asserting the result.

**Still Phase 3:** no checkout. Paid courses show a disabled Buy button; an admin enrols
people by email in the meantime.

### 2026-09-14 — Phase 1 done
`courses / sections / lessons` + admin + public catalog. Verified end to end on
https://oric-lms.local:8890.

**Two shared concerns rather than duplicated logic:**
`Orderable` (position append + neighbour swap, scoped per parent) and `HasUniqueSlug`
(fills a blank slug, suffixes until unique in its own scope — global for Course,
per-section for Lesson). Both are used by two models, which is what earned them.

**Caught while writing the public controller:** `$course->load('sections.lessons')` ships
every lesson body and `video_path` to the browser. On the *public* page that is a leak
today, not a Phase 2 problem. The outline now selects only safe columns, and a test asserts
the response contains neither the body text nor the video path.

**Deliberate simplification:** reordering is up/down buttons, not drag-and-drop. No dnd
dependency, keyboard and screen-reader accessible for free. Marked `ponytail:` in
`edit.tsx` with the upgrade path.

**Not built (Phase 2+):** video upload, enrolment, the player. The course page's Enrol
button is deliberately disabled.

### 2026-09-14 — Filament removed
Dropped Filament entirely: **-27 packages** (105 → 78 prod), Livewire 4 and the whole
Blade/Alpine dependency tree gone with it. The stack is now React/Inertia only, which is
what was asked for from the start — I put Filament in the plan on my own and flagged the
two-UI-stack cost as a risk without actually asking.

**Kept:** `UserRole` enum, `role` column, seeders. Still needed to gate the admin routes.
**Replaced:** the 5 panel-access tests became 2 role tests. The one that mattered survives —
`role` is mass-assignable (seeders and factories need it), so there is a test proving a
registration POST carrying `role: admin` still comes out a student.
**Cost, stated plainly:** Phases 1 and 4 now carry the course builder and quiz builder by
hand. That is the real price of one UI stack, and it lands in Phase 1.

28 tests pass. `/admin` is a 404 again until Phase 1 builds it.

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
