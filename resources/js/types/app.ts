export type Author = {
    name: string;
    username: string | null;
    avatar_url: string;
};

export type ReactionSummary = {
    total: number;
    counts: Record<string, number>;
    mine: string | null;
};

export type RantCard = {
    id: number;
    slug: string;
    title: string;
    excerpt: string;
    source: 'email' | 'web';
    view_count: number;
    comments_count: number;
    bookmarks_count: number;
    reactions_count: number;
    bookmarked: boolean;
    published_at: string | null;
    published_human: string | null;
    author: Author | null;
};

export type RantDetail = {
    id: number;
    slug: string;
    title: string;
    body: string;
    source: 'email' | 'web';
    source_label: string;
    view_count: number;
    bookmarks_count: number;
    bookmarked: boolean;
    reactions: ReactionSummary;
    published_at: string | null;
    published_human: string | null;
    author: Author | null;
};

export type CommentNode = {
    id: number;
    parent_id: number | null;
    body: string;
    created_at: string | null;
    created_human: string | null;
    author: Author | null;
    replies: CommentNode[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: PaginationLink[];
    total: number;
};

export type ScrubReportChange = {
    type: string;
    alias: string;
    count: number;
};

export type ModerationRant = {
    id: number;
    title: string;
    slug: string;
    status: string;
    source: string;
    needs_review: boolean;
    original_body: string;
    scrubbed_body: string | null;
    scrub_report: { driver: string; changes: ScrubReportChange[] } | null;
    author: { name: string; username: string | null } | null;
    created_human: string | null;
    reports_count?: number;
};
