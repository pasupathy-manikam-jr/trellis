import { Cards, HandbookPage, Notes, Section, Steps } from '@/components/handbook-parts';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpen, FileText, ListChecks, Play, Receipt, Tag, Ticket, Users } from 'lucide-react';

const contents = [
    { href: '#roles', label: 'Who can do what' },
    { href: '#build', label: 'Build a course' },
    { href: '#lessons', label: 'Lesson types' },
    { href: '#timing', label: 'Releasing over time' },
    { href: '#learners', label: 'Your learners' },
    { href: '#money', label: 'Money and taxonomy' },
    { href: '#notes', label: 'Worth knowing' },
];

const permissions: [string, string, string][] = [
    ['Their own courses, lessons, quizzes', 'Yes', 'Yes'],
    ['Someone else’s courses', 'No', 'Yes'],
    ['Insights and revenue', 'Own courses', 'Everything'],
    ['Enrol or revoke a learner', 'Own courses', 'Everything'],
    ['Orders and refunds', 'No', 'Yes'],
    ['Coupons', 'No', 'Yes'],
    ['Categories', 'No', 'Yes'],
    ['Change someone’s role', 'No', 'Yes'],
];

export default function AdminHandbook() {
    const { auth } = usePage<SharedData>().props;
    const isAdmin = auth.user?.role === 'admin';

    return (
        <>
            <Head title="Handbook for instructors" />

            <HandbookPage
                eyebrow="Handbook"
                title="Running a course"
                lede="How to build a course, publish it, and look after the people taking it."
                tone="admin"
                contents={contents}
                switcher={{ href: '/handbook', label: 'Taking a course' }}
            >
                <Section
                    id="roles"
                    eyebrow="Before you start"
                    tone="admin"
                    title="Who can do what"
                    lede="Instructors get the course workspace, scoped to courses they own. Money, taxonomy and people stay with admins."
                >
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
                                {permissions.map(([area, instructor, admin]) => (
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
                        An admin cannot demote themselves — that would lock the last admin out of the page
                        that undoes it.
                    </p>
                </Section>

                <Section
                    id="build"
                    eyebrow="Step one"
                    tone="admin"
                    title="Build a course"
                    lede="A course stays invisible to the public until you publish it, so build in the open without worry."
                >
                    <Steps
                        tone="admin"
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
                    <p className="mt-6">
                        <Link href="/admin/courses" className="text-primary text-sm hover:underline">
                            Open your courses →
                        </Link>
                    </p>
                </Section>

                <Section id="lessons" eyebrow="Step two" tone="admin" title="Lesson types">
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
                </Section>

                <Section
                    id="timing"
                    eyebrow="Optional"
                    tone="admin"
                    title="Releasing over time"
                    lede="Every lesson has an “Unlock after” field, in days."
                >
                    <Cards
                        items={[
                            {
                                icon: ListChecks,
                                title: 'Zero opens immediately',
                                body: 'The default. The lesson is available as soon as someone enrols.',
                            },
                            {
                                icon: ListChecks,
                                title: 'Seven opens after a week',
                                body: 'Counted from each learner’s own enrolment date, so people who join at different times get the same schedule.',
                            },
                        ]}
                    />
                    <p className="text-muted-foreground mt-4 max-w-prose text-sm">
                        There is no scheduler to configure and nothing to run — the unlock date is worked out
                        per learner when the page loads. Learners see a calendar icon and the date on anything
                        not yet open.
                    </p>
                </Section>

                <Section id="learners" eyebrow="Day to day" tone="admin" title="Your learners">
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
                            {
                                icon: ListChecks,
                                title: 'Questions',
                                body: 'You are emailed whenever a learner asks something on one of your lessons. Replying to that email reaches them directly.',
                            },
                            {
                                icon: Users,
                                title: 'Reviews',
                                body: 'Only people who enrolled can review, one each. The average shows on the catalogue card and the course page.',
                            },
                        ]}
                    />
                </Section>

                {isAdmin && (
                    <Section
                        id="money"
                        eyebrow="Admins only"
                        tone="admin"
                        title="Money and taxonomy"
                        lede="These four areas are not available to instructors."
                    >
                        <Cards
                            items={[
                                {
                                    icon: Receipt,
                                    title: 'Orders',
                                    body: 'Every order with its buyer, coupon and status. Refunding revokes access and frees the coupon redemption.',
                                    where: '/admin/orders',
                                },
                                {
                                    icon: Ticket,
                                    title: 'Coupons',
                                    body: 'Percentage or fixed amount off, with optional usage cap and expiry. A discount can take a price to zero but never below it.',
                                    where: '/admin/coupons',
                                },
                                {
                                    icon: Tag,
                                    title: 'Categories',
                                    body: 'The filter chips on the catalogue, in the order you set. A course can sit in several at once.',
                                    where: '/admin/categories',
                                },
                                {
                                    icon: Users,
                                    title: 'People',
                                    body: 'Everyone with an account and the role they hold. Promote a learner to instructor here.',
                                    where: '/admin/users',
                                },
                            ]}
                        />
                    </Section>
                )}

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
                                'A quiz lesson cannot be ticked complete by hand.',
                                'It completes only by being passed. That is what makes “finished the course” mean the quizzes were actually answered.',
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
                                'A coupon is spent when the payment confirms, not when checkout starts.',
                                'An abandoned checkout leaves the redemption available for someone else.',
                            ],
                            [
                                'A course with no enrolments shows no completion rate.',
                                'Not zero per cent — nobody failed to finish it, nobody started.',
                            ],
                            [
                                'Video never becomes a public URL.',
                                'It is served through an access check every time, so a link copied out of the page is useless to someone who is not enrolled.',
                            ],
                        ]}
                    />
                </Section>
            </HandbookPage>
        </>
    );
}
