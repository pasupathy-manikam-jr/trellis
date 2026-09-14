<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@lms.test'],
            ['name' => 'Admin', 'password' => 'password', 'role' => UserRole::Admin],
        );

        $student = User::updateOrCreate(
            ['email' => 'student@lms.test'],
            ['name' => 'Student', 'password' => 'password', 'role' => UserRole::Student],
        );

        if (Course::exists()) {
            return;
        }

        $categories = collect(['Backend', 'Databases', 'Frontend', 'Fundamentals'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name])]);

        $course = Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Building a Course Platform',
            'summary' => 'Design the schema, build the course editor, ship the player.',
            'description' => "A worked example that follows this repository's own plan.\n\n"
                .'Start with the data model, add an admin that makes it editable, then open it to learners.',
            'price_cents' => 4900,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        $outline = static::outline();

        foreach ($outline as $title => $lessons) {
            $section = Section::create(['course_id' => $course->id, 'title' => $title]);

            foreach ($lessons as [$lessonTitle, $type, $isPreview, $body]) {
                Lesson::create([
                    'section_id' => $section->id,
                    'title' => $lessonTitle,
                    'type' => $type,
                    'content' => static::tidy($body),
                    'duration_sec' => $type === LessonType::Video ? 540 : null,
                    'is_preview' => $isPreview,
                    // The last section drips a week out, so the feature is visible.
                    'drip_days' => $title === 'Going public' ? 7 : 0,
                ]);
            }
        }

        // A free course so self-enrolment is demonstrable without checkout.
        $free = Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Postgres for people who used MySQL',
            'summary' => 'A short free primer. Enrol in one click.',
            'price_cents' => 0,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        $free->categories()->attach($categories['Databases']);

        $basics = Section::create(['course_id' => $free->id, 'title' => 'Basics']);

        $primer = static::primer();

        $i = 0;

        foreach ($primer as $title => $body) {
            Lesson::create([
                'section_id' => $basics->id,
                'title' => $title,
                'type' => LessonType::Text,
                'content' => static::tidy($body),
                'is_preview' => $i++ === 0,
            ]);
        }

        $course->categories()->attach([
            $categories['Backend']->id,
            $categories['Fundamentals']->id,
        ]);

        $this->seedQuiz($course);

        // Left unbought on purpose: the paid course is the one to try checkout on.
        Coupon::create(['code' => 'LAUNCH20', 'percent_off' => 20]);
        Coupon::create(['code' => 'HALFOFF', 'percent_off' => 50, 'max_redemptions' => 2]);
        Coupon::create(['code' => 'TENOFF', 'amount_off_cents' => 1000]);
        Coupon::create(['code' => 'LASTYEAR', 'percent_off' => 90, 'expires_at' => now()->subDay()]);

        Course::factory()->create([
            'instructor_id' => $admin->id,
            'title' => 'A draft that should not appear publicly',
            'status' => CourseStatus::Draft,
        ]);
    }

    /**
     * The demo course is about this repository, so its lessons can say something
     * true rather than carry filler. A preview lesson a visitor opens and finds
     * empty sells nothing.
     *
     * @return array<string, list<array{0: string, 1: LessonType, 2: bool, 3: string}>>
     */
    public static function outline(): array
    {
        return [
            'Foundations' => [
                ['Why not fork an existing platform', LessonType::Text, true, <<<'TXT'
                    The obvious move is to fork something that already works. We looked hard at that
                    first, and decided against it.

                    Moodle has twenty years of pedagogy in it and a plugin for everything, but the
                    weight is the point: SCORM, LTI, cohorts, weighted gradebooks, competency
                    frameworks. Canvas has the best API design in the sector and a modern interface.
                    Open edX runs at MOOC scale. All three are excellent, and all three would have
                    meant inheriting a decade of migrations nobody here understands.

                    The deciding argument was the shape of the work. Forking means your first six
                    months go on someone else's upgrade path rather than your own product. For a
                    platform whose job is selling courses — catalogue, checkout, a player, a
                    certificate — most of what you inherit is weight you then have to carry.

                    So: build the thin thing, and steal the parts worth stealing. The data model in
                    the next lesson is Moodle's, near enough. The API shapes are Canvas's. What we
                    left out is the interoperability layer, and we left it out on purpose, writing
                    the reasons down so the decision could be revisited rather than forgotten.
                    TXT],

                ['Schema: courses, sections, lessons', LessonType::Text, true, <<<'TXT'
                    A course is a tree three levels deep: a course holds sections, a section holds
                    lessons. That is Moodle's course/section/module model with the names changed, and
                    it has survived twenty years of use, which is a better argument than elegance.

                    Every lesson has a type — text, video, download, quiz or assignment — and the type
                    decides what the player renders and whether the lesson can be ticked complete by
                    hand. The tree is the same whatever the type, which is what keeps the player
                    simple.

                    Two things are deliberately not in the tree. Progress is derived, counted from
                    lesson_completions when asked, so editing a course cannot leave a stale
                    percentage behind. And lessons soft-delete, so removing one from a published
                    course does not orphan the completion records of everyone who already did it.
                    TXT],

                ['Slugs and ordering', LessonType::Text, false, <<<'TXT'
                    Two small pieces of behaviour that every content tree needs, and that are easy to
                    get subtly wrong.

                    Slugs are generated from the title when left blank, and suffixed until unique
                    within their own scope — globally for a course, per-section for a lesson. Two
                    lessons in different sections may both be called "Setup"; two courses may not.
                    An explicitly typed slug is never overwritten.

                    Ordering is a position column kept contiguous within a parent, with a move that
                    swaps places with the adjacent sibling. Both live in one concern shared by every
                    orderable thing, so sections, lessons and quiz questions all behave identically
                    rather than each drifting its own way.
                    TXT],
            ],

            'The admin' => [
                ['Route groups and role middleware', LessonType::Text, false, <<<'TXT'
                    There are three roles — admin, instructor, student — and the interesting question
                    is not who gets in, but what they see once inside.

                    The workspace is shared: admins and instructors both reach courses, lessons,
                    quizzes and insights. What differs is scope, and scope is a policy rather than a
                    gate. CoursePolicy@manage is the single rule, and every staff action routes
                    through it — including the nested ones. An instructor cannot reach another
                    instructor's lesson, quiz or enrolment by guessing a URL, because the check
                    happens against the owning course rather than against the URL.

                    That distinction matters. A middleware sees /admin/lessons/42 and has no idea
                    whose lesson 42 is. Money, categories and people stay admin-only, which is a
                    gate; ownership is a policy.
                    TXT],

                ['The course editor', LessonType::Video, false, <<<'TXT'
                    The editor is one page: course details at the top, the curriculum below it,
                    enrolments at the foot.

                    Sections and lessons reorder with arrow buttons rather than drag-and-drop — no
                    dependency, and keyboard and screen-reader accessible without extra work. Each
                    lesson row carries a private/preview toggle, because a course nobody can sample
                    is a course nobody buys.

                    Lesson bodies, quiz questions and assignment briefs are all edited in a dialog
                    from that same list, so building a course never means leaving the page and
                    losing your place.
                    TXT],
            ],

            'Going public' => [
                ['Catalog and detail pages', LessonType::Text, false, <<<'TXT'
                    The catalogue is search, category filters, a free/paid toggle and three sort
                    orders, paginated twelve at a time. Filters combine rather than replace each
                    other, and they survive paging, which is the part people notice only when it is
                    broken.

                    Search is ILIKE over title and summary, with percent and underscore escaped so a
                    literal percent stays literal. That is right for a catalogue this size and wrong
                    for a large one; the ceiling is written into the code so the next person knows
                    when to reach for a full-text index instead of discovering it under load.
                    TXT],

                ['What never reaches the browser', LessonType::Text, false, <<<'TXT'
                    The most important thing this course page does is leave things out.

                    The public outline lists what a course contains — titles, types, durations — and
                    never lesson bodies or video paths. Loading the obvious relation would have
                    shipped every paid lesson to anyone who viewed the source.

                    Video lives on the private disk and is streamed through a route that applies the
                    same policy as the lesson page. Serving it from public storage would have made
                    the access check decorative: one URL, copied once, and the gate is gone. Quiz
                    options ship without their is_correct flag. Coursework a learner hands in is
                    readable by its author and the course owner, nobody else.

                    Each of those has a test that asserts the absence, because absence is the kind of
                    thing that regresses quietly.
                    TXT],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function primer(): array
    {
        return [
            'Types that actually exist' => <<<'TXT'
                MySQL will take almost anything you give it and quietly make it fit. Postgres will
                not, and that is the feature.

                There is a real boolean, not a tinyint pretending. There is a native uuid, inet and
                interval. Text has no length penalty, so varchar(255) is a habit you can drop. And
                timestamptz stores an actual instant rather than a wall-clock reading whose timezone
                you are expected to remember.

                The practical effect is that a column's type tells you what can be in it. A boolean
                column holds true or false, not 2, not the string 'yes', not an empty string that a
                loose comparison will read as false at the worst moment.
                TXT,

            'Sequences, not AUTO_INCREMENT' => <<<'TXT'
                Postgres has no AUTO_INCREMENT. An identity column is backed by a sequence, which is
                an object in its own right — you can look at it, grant on it, and set it.

                That independence is worth knowing about the first time you import data with explicit
                ids and then watch the next insert collide. The sequence did not move when you did
                the import, because nothing told it to. setval fixes it, and knowing why is what
                stops the same surprise twice.

                The upside of a separate object is that it does not lock. Two transactions can both
                take a value and neither waits, which is exactly what you want on a busy table.
                TXT,

            'JSONB' => <<<'TXT'
                jsonb is not a text column with a nice name. It is parsed on write, stored in a
                binary form, and indexable — you can put a GIN index on it and query inside the
                document rather than fetching the whole thing and picking it apart in PHP.

                The temptation is then to put everything in it. Resist that. The moment a field is
                queried, sorted or constrained, it wants to be a column, where the type system and
                the planner can help. jsonb earns its place for the genuinely shapeless: a payload
                from somewhere else, a set of options that differ per row.

                This platform uses exactly one, for the option ids on a quiz answer, and that one is
                borderline.
                TXT,
        ];
    }

    /** Heredocs in a seeder arrive indented; lesson bodies should not be. */
    public static function tidy(string $body): string
    {
        return trim(preg_replace('/^ {0,20}/m', '', $body));
    }

    /** A short quiz on the last section, so finishing the course means passing it. */
    private function seedQuiz(Course $course): void
    {
        $lesson = Lesson::create([
            'section_id' => $course->sections()->orderByDesc('position')->first()->id,
            'title' => 'Check what you learned',
            'type' => LessonType::Quiz,
        ]);

        $quiz = Quiz::create(['lesson_id' => $lesson->id, 'pass_percent' => 70, 'max_attempts' => 3]);

        $bank = [
            ['Where should gated video files live?', QuestionType::Single, [
                ['storage/app/private — behind an access check', true],
                ['storage/app/public — it is faster', false],
                ['In the database as a blob', false],
            ]],
            ['Which belong in a course-selling LMS? Pick all.', QuestionType::Multi, [
                ['Enrolments', true],
                ['A gradebook with weighted categories', false],
                ['Certificates', true],
                ['SCORM packages', false],
            ]],
            ['Why is progress computed rather than stored?', QuestionType::Single, [
                ['Editing a course cannot leave a stale percentage', true],
                ['It is faster to query', false],
                ['Postgres cannot store integers', false],
            ]],
        ];

        foreach ($bank as [$prompt, $type, $options]) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'type' => $type,
                'prompt' => $prompt,
                'points' => $type === QuestionType::Multi ? 2 : 1,
            ]);

            foreach ($options as [$text, $correct]) {
                Option::create([
                    'question_id' => $question->id,
                    'text' => $text,
                    'is_correct' => $correct,
                ]);
            }
        }
    }
}
