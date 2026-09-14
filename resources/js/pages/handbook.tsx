import PublicLayout from '@/layouts/public-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Award,
    BookOpen,
    CalendarClock,
    CreditCard,
    FileText,
    ListChecks,
    MessageSquare,
    Play,
    Receipt,
    Search,
    Tag,
    Users,
} from 'lucide-react';

/** Section heading with the eyebrow that says which audience it serves. */
function Heading({
    eyebrow,
    tone,
    title,
    lede,
}: {
    eyebrow: string;
    tone: 'learner' | 'admin' | 'note';
    title: string;
    lede?: string;
}) {
    const colour = {
        learner: 'text-primary',
        admin: 'text-amber-700 dark:text-amber-400',
        note: 'text-muted-foreground',
    }[tone];

    return (
        <>
            <p className={`flex items-center gap-2 text-xs font-semibold tracking-widest uppercase ${colour}`}>
                <span className="size-1.5 rotate-45 bg-current" aria-hidden />
                {eyebrow}
            </p>
            <h2 className="mt-2 text-2xl font-bold tracking-tight text-balance">{title}</h2>
            {lede && <p className="text-muted-foreground mt-2 max-w-prose">{lede}</p>}
        </>
    );
}

function Cards({ items }: { items: { icon: typeof Play; title: string; body: string; where?: string }[] }) {
    return (
        <div className="mt-6 grid overflow-hidden rounded-xl border sm:grid-cols-2">
            {items.map(({ icon: Icon, title, body, where }, i) => (
                <div
                    key={title}
                    className={`bg-card p-5 ${i % 2 === 0 ? 'sm:border-r' : ''} ${
                        i < items.length - (items.length % 2 === 0 ? 2 : 1) ? 'border-b' : ''
                    }`}
                >
                    <h3 className="flex items-center gap-2 font-semibold">
                        <Icon className="text-muted-foreground size-4" />
                        {title}
                    </h3>
                    <p className="text-muted-foreground mt-1.5 text-sm">{body}</p>
                    {where && <code className="text-muted-foreground mt-2 block text-xs">{where}</code>}
                </div>
            ))}
        </div>
    );
}

function Steps({ items }: { items: { title: string; body: React.ReactNode }[] }) {
    return (
        <ol className="mt-6 flex flex-col gap-6">
            {items.map((step, i) => (
                <li key={step.title} className="grid grid-cols-[2rem_1fr] items-start gap-4">
                    <span className="bg-card text-primary grid size-8 place-items-center rounded-full border font-mono text-xs font-semibold tabular-nums">
                        {i + 1}
                    </span>
                    <div>
                        <h3 className="font-semibold">{step.title}</h3>
                        <div className="text-muted-foreground mt-1 max-w-prose text-sm">{step.body}</div>
                    </div>
                </li>
            ))}
        </ol>
    );
}

const contents = [
    { href: '#find', label: 'Find a course' },
    { href: '#enrol', label: 'Enrol' },
    { href: '#learn', label: 'Work through it' },
    { href: '#quizzes', label: 'Quizzes' },
    { href: '#questions', label: 'Ask a question' },
    { href: '#certificates', label: 'Certificates' },
    { href: '#roles', label: 'Who can do what' },
    { href: '#build', label: 'Build a course' },
    { href: '#lessons', label: 'Lesson types' },
    { href: '#learners', label: 'Your learners' },
    { href: '#surprises', label: 'Things that surprise people' },
];

export default function Handbook() {
    const { auth } = usePage<SharedData>().props;
    const staff = auth.user?.role === 'admin' || auth.user?.role === 'instructor';

    return (
        <PublicLayout>
            <Head title="Handbook" />

            <div className="mb-10">
                <span className="text-primary text-xs font-semibold tracking-widest uppercase">Handbook</span>
                <h1 className="mt-2 text-3xl font-bold tracking-tight">How Trellis works</h1>
                <div className="bg-primary mt-4 h-1 w-16 rounded-full" />
                <p className="text-muted-foreground mt-4 max-w-prose">
                    How to take a course, and how to run one.
                </p>
            </div>

            <nav aria-label="Contents" className="mb-12 flex flex-wrap gap-x-4 gap-y-1 border-y py-4">
                {contents.map((item) => (
                    <a
                        key={item.href}
                        href={item.href}
                        className="text-muted-foreground hover:text-foreground text-sm"
                    >
                        {item.label}
                    </a>
                ))}
            </nav>

            <div className="flex flex-col gap-14">
                <section id="find" className="scroll-mt-6">
                    <Heading
                        eyebrow="For learners"
                        tone="learner"
                        title="Find a course"
                        lede="The catalogue is open to everyone — no account needed to browse."
                    />
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
                            },
                        ]}
                    />
                    <p className="mt-4">
                        <Link href="/courses" className="text-primary text-sm hover:underline">
                            Browse the catalogue →
                        </Link>
                    </p>
                </section>

                <section id="enrol" className="scroll-mt-6">
                    <Heading
                        eyebrow="For learners"
                        tone="learner"
                        title="Enrol"
                        lede="Every course opens a lesson or two to everyone, so you can read before you decide."
                    />
                    <Steps
                        items={[
                            {
                                title: 'Read a preview first',
                                body: 'Lessons marked “Free preview” are readable without an account. The rest show as locked.',
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
                                        spent when a payment actually confirms — abandoning checkout does not use
                                        one up.
                                    </>
                                ),
                            },
                        ]}
                    />
                </section>

                <section id="learn" className="scroll-mt-6">
                    <Heading
                        eyebrow="For learners"
                        tone="learner"
                        title="Work through it"
                        lede="The player keeps the course outline on the left and the lesson on the right."
                    />
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
                                title: 'Drip',
                                body: 'Some courses release lessons over time. The clock starts the day you enrolled, not the day the course was published.',
                            },
                        ]}
                    />
                </section>

                <section id="quizzes" className="scroll-mt-6">
                    <Heading
                        eyebrow="For learners"
                        tone="learner"
                        title="Quizzes"
                        lede="A quiz lesson is the one kind you cannot simply tick off."
                    />
                    <div className="text-muted-foreground mt-4 flex max-w-prose flex-col gap-3">
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
                    </div>
                </section>

                <section id="questions" className="scroll-mt-6">
                    <Heading
                        eyebrow="For learners"
                        tone="learner"
                        title="Ask a question"
                        lede="Every lesson has a Questions section underneath it."
                    />
                    <div className="text-muted-foreground mt-4 flex max-w-prose flex-col gap-3">
                        <p>
                            Ask on the lesson you are stuck on and whoever owns the course is emailed straight
                            away. Other enrolled learners can answer too; replies from the course owner are
                            badged <em>instructor</em>.
                        </p>
                        <p>
                            You or the instructor can mark a question answered, and reopen it later. Threads stay
                            one level deep — a question and its answers.
                        </p>
                    </div>
                </section>

                <section id="certificates" className="scroll-mt-6">
                    <Heading
                        eyebrow="For learners"
                        tone="learner"
                        title="Certificates"
                        lede="Finish every lesson — including passing any quizzes — and a certificate is issued automatically."
                    />
                    <div className="text-muted-foreground mt-4 flex max-w-prose flex-col gap-3">
                        <p>
                            It appears in the player sidebar and on your dashboard, and downloads as a PDF. Each
                            one carries a serial in the form <code className="text-foreground">LMS-XXXX-XXXX-XXXX</code>.
                        </p>
                        <p>
                            Anyone can confirm a certificate is genuine at{' '}
                            <code className="text-foreground">/verify/&lt;serial&gt;</code> — no account
                            required. That page shows who it was awarded to, for which course, and when.
                        </p>
                    </div>
                </section>

                <section id="roles" className="scroll-mt-6">
                    <Heading
                        eyebrow="For admins and instructors"
                        tone="admin"
                        title="Who can do what"
                        lede="Instructors get the course workspace, scoped to courses they own. Money, taxonomy and people stay with admins."
                    />

                    <div className="mt-6 overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[460px] text-sm">
                            <thead className="bg-muted/50 text-muted-foreground border-b text-left">
                                <tr>
                                    <th className="px-4 py-2.5 text-xs font-semibold tracking-widest uppercase">
                                        Area
                                    </th>
                                    <th className="w-32 px-4 py-2.5 text-center text-xs font-semibold tracking-widest uppercase">
                                        Instructor
                                    </th>
                                    <th className="w-32 px-4 py-2.5 text-center text-xs font-semibold tracking-widest uppercase">
                                        Admin
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {[
                                    ['Their own courses, lessons, quizzes', 'Yes', 'Yes'],
                                    ['Someone else’s courses', 'No', 'Yes'],
                                    ['Insights and revenue', 'Own courses', 'Everything'],
                                    ['Enrol or revoke a learner', 'Own courses', 'Everything'],
                                    ['Orders and refunds', 'No', 'Yes'],
                                    ['Coupons', 'No', 'Yes'],
                                    ['Categories', 'No', 'Yes'],
                                    ['Change someone’s role', 'No', 'Yes'],
                                ].map(([area, instructor, admin]) => (
                                    <tr key={area} className="border-b last:border-0">
                                        <td className="px-4 py-2.5">{area}</td>
                                        <td
                                            className={`px-4 py-2.5 text-center ${
                                                instructor === 'No'
                                                    ? 'text-muted-foreground'
                                                    : 'text-primary font-medium'
                                            }`}
                                        >
                                            {instructor}
                                        </td>
                                        <td className="text-primary px-4 py-2.5 text-center font-medium">
                                            {admin}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <p className="text-muted-foreground mt-3 text-xs">
                        An admin cannot demote themselves — that would lock the last admin out of the page that
                        undoes it.
                    </p>
                </section>

                <section id="build" className="scroll-mt-6">
                    <Heading
                        eyebrow="For admins and instructors"
                        tone="admin"
                        title="Build a course"
                        lede="A course stays invisible to the public until you publish it, so build in the open without worry."
                    />
                    <Steps
                        items={[
                            {
                                title: 'Create it',
                                body: 'A title and one-line summary is enough to start. The URL slug is generated from the title, and you can override it.',
                            },
                            {
                                title: 'Fill in the details',
                                body: 'Summary, description, price in cents (0 means free), categories, and a cover image. The cover shows on the catalogue and behind the course header.',
                            },
                            {
                                title: 'Add sections, then lessons',
                                body: 'Sections group lessons. Both reorder with the arrow buttons. Click any lesson title to edit it.',
                            },
                            {
                                title: 'Mark a preview or two',
                                body: 'Tick “Free preview” on a lesson to open it to everyone. This is what sells the course, so pick something that stands alone.',
                            },
                            {
                                title: 'Publish',
                                body: 'Set status to Published and save. The publish date is stamped once and kept, so unpublishing and republishing does not reorder your catalogue.',
                            },
                        ]}
                    />
                </section>

                <section id="lessons" className="scroll-mt-6">
                    <Heading eyebrow="For admins and instructors" tone="admin" title="Lesson types" />
                    <Cards
                        items={[
                            {
                                icon: FileText,
                                title: 'Text',
                                body: 'Plain body copy. The default, and the quickest to write.',
                            },
                            {
                                icon: Play,
                                title: 'Video',
                                body: 'Upload an MP4, WebM or MOV up to 500 MB. Stored privately and streamed through an access check, so the file cannot be passed around.',
                            },
                            {
                                icon: BookOpen,
                                title: 'Download',
                                body: 'For a worksheet or resource alongside the written content.',
                            },
                            {
                                icon: ListChecks,
                                title: 'Quiz',
                                body: 'Set a pass mark and optional attempt limit, then add single or multiple choice questions with points each.',
                            },
                        ]}
                    />
                    <p className="text-muted-foreground mt-4 max-w-prose text-sm">
                        <strong className="text-foreground">Drip.</strong> Every lesson has an{' '}
                        <em>Unlock after</em> field in days. 0 opens it immediately; 7 opens it a week after each
                        learner enrols. There is no scheduler to configure — it is worked out per learner when
                        the page loads.
                    </p>
                </section>

                <section id="learners" className="scroll-mt-6">
                    <Heading eyebrow="For admins and instructors" tone="admin" title="Your learners" />
                    <Cards
                        items={[
                            {
                                icon: Users,
                                title: 'Enrol someone by hand',
                                body: 'The Enrolments panel at the foot of the course editor. Enter an email to grant access, with each learner’s progress beside them.',
                            },
                            {
                                icon: Receipt,
                                title: 'Insights',
                                body: 'Revenue, enrolments, completion rate and rating per course. Revenue counts paid orders only; refunds are excluded.',
                                where: '/admin/insights',
                            },
                        ]}
                    />
                    {staff && (
                        <p className="mt-4">
                            <Link href="/admin/courses" className="text-primary text-sm hover:underline">
                                Open your courses →
                            </Link>
                        </p>
                    )}
                </section>

                <section id="surprises" className="scroll-mt-6">
                    <Heading
                        eyebrow="Reference"
                        tone="note"
                        title="Things that surprise people"
                        lede="Behaviour that is deliberate, but not obvious."
                    />

                    <ul className="mt-6 flex flex-col gap-4">
                        {[
                            [
                                'A quiz lesson cannot be ticked complete by hand.',
                                'It completes only by being passed. That is what makes “finished the course” mean the quizzes were actually answered.',
                            ],
                            [
                                'Drip counts from each learner’s enrolment date.',
                                'Not from when the course was published. Two people who join a month apart see the same schedule, offset.',
                            ],
                            [
                                'Deleting a category never deletes courses.',
                                'They simply lose the label.',
                            ],
                            [
                                'A refund revokes access immediately.',
                                'Otherwise the money goes back and the course does not. It also returns the coupon redemption to the pool.',
                            ],
                            [
                                'A certificate is never taken back.',
                                'Un-ticking a lesson reopens the course, but the certificate attests the course was finished, and it stands.',
                            ],
                            [
                                'Preview lessons are readable but not answerable.',
                                'A visitor can read a preview lesson and its question thread, but cannot post without enrolling.',
                            ],
                            [
                                'Video never becomes a public URL.',
                                'It is served through an access check every time, so a link copied out of the page is useless to someone who is not enrolled.',
                            ],
                        ].map(([title, body]) => (
                            <li key={title} className="border-l-2 border-amber-500/70 pl-4">
                                <strong className="block text-sm font-semibold">{title}</strong>
                                <span className="text-muted-foreground text-sm">{body}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </PublicLayout>
    );
}
