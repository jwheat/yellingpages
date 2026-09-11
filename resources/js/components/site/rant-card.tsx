import { Link, router, usePage } from '@inertiajs/react';
import { Bookmark, MessageSquare, Mail } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { RantCard as RantCardType } from '@/types/app';

export default function RantCard({ rant }: { rant: RantCardType }) {
    const getInitials = useInitials();
    const user = usePage().props.auth?.user ?? null;

    const toggleBookmark = () => {
        if (!user) {
            router.visit('/login');

            return;
        }

        const url = `/rants/${rant.slug}/bookmark`;

        if (rant.bookmarked) {
            router.delete(url, { preserveScroll: true });
        } else {
            router.post(url, {}, { preserveScroll: true });
        }
    };

    return (
        <Card className="overflow-hidden transition hover:shadow-md">
            <CardContent className="pt-0">
                <div className="mb-2 flex items-center gap-2 text-sm text-muted-foreground">
                    {rant.author ? (
                        <Link
                            href={`/@${rant.author.username}`}
                            className="flex items-center gap-2 hover:text-foreground"
                        >
                            <Avatar className="size-6">
                                <AvatarImage src={rant.author.avatar_url} />
                                <AvatarFallback>
                                    {getInitials(rant.author.name)}
                                </AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{rant.author.name}</span>
                        </Link>
                    ) : (
                        <span className="flex items-center gap-1 italic">
                            <Mail className="size-4" /> Anonymous emailer
                        </span>
                    )}
                    {rant.source === 'email' && (
                        <Badge variant="secondary" className="text-[10px]">
                            via email
                        </Badge>
                    )}
                    <span className="ml-auto text-xs">{rant.published_human}</span>
                </div>

                <Link href={`/rants/${rant.slug}`} className="group block">
                    <h2 className="text-lg font-bold leading-snug group-hover:underline">
                        {rant.title}
                    </h2>
                    <p className="mt-1 line-clamp-3 text-sm text-muted-foreground">
                        {rant.excerpt}
                    </p>
                </Link>
            </CardContent>

            <CardFooter className="gap-4 text-sm text-muted-foreground">
                <Link
                    href={`/rants/${rant.slug}`}
                    className="flex items-center gap-1 hover:text-foreground"
                >
                    <MessageSquare className="size-4" /> {rant.comments_count}
                </Link>
                <button
                    onClick={toggleBookmark}
                    className={cn(
                        'flex items-center gap-1 transition hover:text-foreground',
                        rant.bookmarked && 'text-yellow-600 dark:text-yellow-400',
                    )}
                >
                    <Bookmark
                        className={cn('size-4', rant.bookmarked && 'fill-current')}
                    />{' '}
                    {rant.bookmarks_count}
                </button>
                {rant.reactions_count > 0 && (
                    <span className="flex items-center gap-1" title="reactions">
                        🔥 {rant.reactions_count}
                    </span>
                )}
                <span className="ml-auto text-xs">{rant.view_count} reads</span>
            </CardFooter>
        </Card>
    );
}
