import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/app';

export default function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <div className="mt-6 flex items-center justify-between">
            <Button variant="outline" size="sm" asChild disabled={!page.prev_page_url}>
                {page.prev_page_url ? (
                    <Link href={page.prev_page_url} preserveScroll>
                        ← Newer
                    </Link>
                ) : (
                    <span>← Newer</span>
                )}
            </Button>
            <span className="text-sm text-muted-foreground">
                Page {page.current_page} of {page.last_page}
            </span>
            <Button variant="outline" size="sm" asChild disabled={!page.next_page_url}>
                {page.next_page_url ? (
                    <Link href={page.next_page_url} preserveScroll>
                        Older →
                    </Link>
                ) : (
                    <span>Older →</span>
                )}
            </Button>
        </div>
    );
}
