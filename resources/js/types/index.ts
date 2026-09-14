import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success: string | null };
    [key: string]: unknown;
}

export type UserRole = 'admin' | 'instructor' | 'student';

export type CourseStatus = 'draft' | 'published' | 'archived';

export type LessonType = 'video' | 'text' | 'download' | 'quiz';

export interface Lesson {
    id: number;
    section_id: number;
    slug: string;
    title: string;
    type: LessonType;
    content: string | null;
    duration_sec: number | null;
    position: number;
    is_preview: boolean;
    quiz?: Quiz | null;
}

export type QuestionType = 'single' | 'multi';

export interface QuizOption {
    id: number;
    text: string;
    is_correct?: boolean;
}

export interface QuizQuestion {
    id: number;
    type: QuestionType;
    prompt: string;
    points: number;
    options: QuizOption[];
}

export interface Quiz {
    id: number;
    pass_percent: number;
    max_attempts: number | null;
    shuffle: boolean;
    questions: QuizQuestion[];
}

export interface PlayerQuiz {
    id: number;
    pass_percent: number;
    max_attempts: number | null;
    attempts_left: number | null;
    attempts_taken: number;
    passed: boolean;
    can_attempt: boolean;
    best_score: number | null;
    questions: QuizQuestion[];
}

export interface Section {
    id: number;
    course_id: number;
    title: string;
    position: number;
    lessons: Lesson[];
}

export interface OutlineLesson {
    id: number;
    slug: string;
    title: string;
    type: LessonType;
    duration_sec: number | null;
    is_preview: boolean;
    completed: boolean;
    locked: boolean;
}

export interface OutlineSection {
    id: number;
    title: string;
    lessons: OutlineLesson[];
}

export interface PlayerLesson {
    id: number;
    slug: string;
    title: string;
    type: LessonType;
    content: string | null;
    duration_sec: number | null;
    is_preview: boolean;
    completed: boolean;
    video_url: string | null;
}

export interface Progress {
    completed: number;
    total: number;
    percent: number;
}

export interface Course {
    id: number;
    instructor_id: number;
    slug: string;
    title: string;
    summary: string | null;
    description: string | null;
    price_cents: number;
    currency: string;
    status: CourseStatus;
    published_at: string | null;
    sections?: Section[];
    sections_count?: number;
    lessons_count?: number;
    instructor?: Pick<User, 'id' | 'name'>;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
