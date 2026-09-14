import { Cards, HandbookPage, Notes, Prose, Section, Steps } from '@/components/handbook-parts';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpen, CalendarClock, CreditCard, Download, ListChecks, Lock, Play, Search, Tag } from 'lucide-react';

const contents = [
    { href: '#find', label: 'Find a course' },
    { href: '#enrol', label: 'Enrol' },
    { href: '#player', label: 'Work through it' },
    { href: '#quizzes', label: 'Quizzes' },
    { href: '#assignments', label: 'Assignments' },
    { href: '#grades', label: 'Your grade' },
    { href: '#questions', label: 'Ask a question' },
    { href: '#certificates', label: 'Certificates' },
    { href: '#notes', label: 'Worth knowing' },
];

export default function LearnerHandbook() {
    const { auth } = usePage<SharedData>().props;
    const staff = auth.user?.role === 'admin' || auth.user?.role === 'instructor';

    return (
        <>
            <Head title="Handbook" />

            <HandbookPage
                eyebrow="Handbook"
                title="Taking a course"
                lede="How to find something to learn, work through it, and come away with a certificate."
                tone="learner"
                href="/handbook"
                contents={contents}
                switcher={staff ? { href: '/handbook/admin', label: 'Running a course' } : undefined}
            >
                <Section
                    id="find"
                    eyebrow="Step one"
                    tone="learner"
                    title="Find a course"
                    lede="The catalogue is open to everyone — you do not need an account to browse."
                >
                    <Cards
                        items={[
                            {
                                icon: Search,
                                title: 'Search',
                                body: 'Matches course titles and summaries as you type. Case does not matter.',
                            },
                            {
                                icon: Tag,
                                title: 'Categories',
                                body: 'Chips with a count each. Click to filter, click again to clear.',
                            },
                            {
                                icon: CreditCard,
                                title: 'Free or paid',
                                body: 'A three-way toggle that combines with the other filters rather than replacing them.',
                            },
                            {
                                icon: BookOpen,
                                title: 'Sort',
                                body: 'Newest, most enrolled, or best rated. Twelve results a page.',
                                where: '/courses',
                            },
                        ]}
                    />
                    <p className="mt-4">
                        <Link href="/courses" className="text-primary text-sm hover:underline">
                            Browse the catalogue →
                        </Link>
                    </p>
                </Section>

                <Section
                    id="enrol"
                    eyebrow="Step two"
                    tone="learner"
                    title="Enrol"
                    lede="Every course opens a lesson or two to everyone, so you can read before you decide."
                >
                    <Steps
                        items={[
                            {
                                title: 'Read a preview first',
                                body: 'Lessons badged “preview” on the course page are readable in full without an account — click the title to open one. The rest are listed but not clickable.',
                            },
                            {
                                title: 'Free courses take one click',
                                body: 'Enrol for free puts the course straight on your dashboard. No checkout.',
                            },
                            {
                                title: 'Paid courses go through checkout',
                                body: 'You leave for a payment page showing what you owe, then come back enrolled.',
                            },
                            {
                                title: 'Coupons',
                                body: (
                                    <>
                                        Use <strong>Have a coupon?</strong> on the course page. A coupon is only
                                        spent when a payment confirms — abandoning checkout does not use one up.
                                    </>
                                ),
                            },
                        ]}
                    />
                </Section>

                <Section
                    id="player"
                    eyebrow="Step three"
                    tone="learner"
                    title="Work through it"
                    lede="The player keeps the course outline on the left and the lesson on the right."
                >
                    <Cards
                        items={[
                            {
                                icon: Play,
                                title: 'Continue where you left off',
                                body: 'Continue learning returns you to the first lesson you have not finished — not back to lesson one.',
                            },
                            {
                                icon: ListChecks,
                                title: 'Mark complete',
                                body: 'A button at the foot of each lesson. The progress bar and your dashboard update immediately.',
                            },
                            {
                                icon: CalendarClock,
                                title: 'Locked lessons',
                                body: 'A padlock means enrol to unlock. A calendar icon with a date means the lesson opens later.',
                            },
                            {
                                icon: CalendarClock,
                                title: 'Lessons that arrive over time',
                                body: 'Some courses release lessons gradually. The clock starts the day you enrolled, not the day the course was published.',
                            },
                            {
                                icon: Lock,
                                title: 'Lessons that wait on another',
                                body: 'A lesson may open only once you have finished an earlier one. The outline tells you which, rather than showing a bare padlock.',
                            },
                            {
                                icon: Download,
                                title: 'Downloads',
                                body: 'Some lessons carry a worksheet or resource. It appears as a download button on the lesson, and is only yours once you are enrolled.',
                            },
                        ]}
                    />
                </Section>

                <Section
                    id="quizzes"
                    eyebrow="Step four"
                    tone="learner"
                    title="Quizzes"
                    lede="A quiz lesson is the one kind you cannot simply tick off."
                >
                    <Prose>
                        <p>
                            Each quiz has a pass mark and may limit attempts — both are shown before you start.
                            Multiple-choice questions score all-or-nothing: selecting every box gets you zero,
                            not full marks. Questions can be worth different points, so the score is weighted
                            rather than a simple count.
                        </p>
                        <p>
                            Passing marks the lesson complete automatically. Once passed, a quiz cannot be
                            retaken, even if attempts remain.
                        </p>
                    </Prose>
                </Section>

                <Section
                    id="assignments"
                    eyebrow="Step five"
                    tone="learner"
                    title="Assignments"
                    lede="Work you hand in for someone to read and mark."
                >
                    <Prose>
                        <p>
                            An assignment lesson shows the brief, what it is worth, and a deadline if the
                            course sets one. Write your answer in the box, attach a file, or both.
                        </p>
                        <p>
                            <strong className="text-foreground">Handing in completes the lesson</strong> —
                            doing the work is what counts, not the mark you get for it. You can keep
                            changing what you handed in right up until it is marked; after that it is the
                            record of what was marked, so it is fixed.
                        </p>
                        <p>
                            Deadlines are counted from the day <em>you</em> enrolled, not a fixed date. Late
                            work is accepted and flagged rather than refused — it is up to the instructor
                            what that costs you.
                        </p>
                    </Prose>
                </Section>

                <Section
                    id="grades"
                    eyebrow="Any time"
                    tone="learner"
                    title="Your grade"
                    lede="Quizzes and assignments both count toward one course grade."
                >
                    <Prose>
                        <p>
                            Each graded thing is worth a share of the total, and the instructor decides how
                            big that share is — a final project can count for more than a warm-up quiz.
                        </p>
                        <p>
                            <strong className="text-foreground">Work nobody has marked yet is left out</strong>,
                            rather than counted as zero. So your grade reflects how you are doing on what has
                            actually been looked at, and reads as nothing at all until the first mark lands.
                        </p>
                        <p>
                            For quizzes the gradebook keeps your <em>best</em> attempt, so retaking one for
                            practice can never cost you marks.
                        </p>
                    </Prose>
                </Section>

                <Section
                    id="questions"
                    eyebrow="Any time"
                    tone="learner"
                    title="Ask a question"
                    lede="Every lesson has a Questions section underneath it."
                >
                    <Prose>
                        <p>
                            Ask on the lesson you are stuck on and whoever owns the course is emailed straight
                            away. Other enrolled learners can answer too; replies from the course owner are
                            badged <em>instructor</em>.
                        </p>
                        <p>
                            You or the instructor can mark a question answered, and reopen it later. Threads stay
                            one level deep — a question and its answers.
                        </p>
                    </Prose>
                </Section>

                <Section
                    id="certificates"
                    eyebrow="At the end"
                    tone="learner"
                    title="Certificates"
                    lede="Finish every lesson — including passing any quizzes — and a certificate is issued automatically."
                >
                    <Prose>
                        <p>
                            It appears in the player sidebar and on{' '}
                            <Link href="/dashboard" className="text-primary hover:underline">
                                your dashboard
                            </Link>
                            , and downloads as a PDF. Each one carries a serial like{' '}
                            <code className="text-foreground">LMS-XXXX-XXXX-XXXX</code>.
                        </p>
                        <p>
                            Anyone can confirm a certificate is genuine at{' '}
                            <code className="text-foreground">/verify/&lt;serial&gt;</code> — no account needed.
                            That page shows who it was awarded to, for which course, and when.
                        </p>
                    </Prose>
                </Section>

                <Section
                    id="notes"
                    eyebrow="Reference"
                    tone="note"
                    title="Worth knowing"
                    lede="Behaviour that is deliberate, but not obvious."
                >
                    <Notes
                        items={[
                            [
                                'Quiz and assignment lessons cannot be ticked complete by hand.',
                                'A quiz completes by being passed, an assignment by being handed in — which is what makes finishing a course mean something.',
                            ],
                            [
                                'Unmarked work does not drag your grade down.',
                                'It is left out of the average entirely, rather than counted as zero.',
                            ],
                            [
                                'A locked lesson tells you why.',
                                'Either a date it opens, or the lesson you need to finish first — never an unexplained padlock.',
                            ],
                            [
                                'Timed lessons count from the day you enrolled.',
                                'Not from when the course was published. Two people who join a month apart get the same schedule, offset.',
                            ],
                            [
                                'A certificate is never taken back.',
                                'Un-ticking a lesson reopens the course, but the certificate attests you did finish it, and it stands.',
                            ],
                            [
                                'You can read a preview without being able to post on it.',
                                'Preview lessons show their question thread, but asking needs an enrolment.',
                            ],
                        ]}
                    />
                </Section>
            </HandbookPage>
        </>
    );
}
