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

export type LessonType = 'video' | 'text' | 'download';

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
}

export interface Section {
    id: number;
    course_id: number;
    title: string;
    position: number;
    lessons: Lesson[];
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
