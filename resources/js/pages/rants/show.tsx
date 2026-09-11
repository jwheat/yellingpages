import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Bookmark, Flag, Mail } from 'lucide-react';
import { useState } from 'react';
import ComebackGenerator from '@/components/site/comeback-generator';
import CommentThread from '@/components/site/comment-thread';
import ReactionBar from '@/components/site/reaction-bar';
import ShareButtons from '@/components/site/share-buttons';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { CommentNode, RantDetail } from '@/types/app';

type Props = {
    rant: RantDetail;
    comments: CommentNode[];
    canModerate: boolean;
    isPreview: boolean;
};

function ReportDialog({ slug }: { slug: string }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        reason: 'defamatory',
        details: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/rants/${slug}/report`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" className="text-muted-foreground">
                    <Flag className="size-4" /> Report
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Report this rant</DialogTitle>
                        <DialogDescription>
                            Tell a moderator what's wrong and we'll take a look.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-1">
                            <Label>Reason</Label>
                            <Select
                                value={data.reason}
                                onValueChange={(v) => setData('reason', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="defamatory">
                                        Defamatory / names a real person
                                    </SelectItem>
                                    <SelectItem value="pii">
                                        Contains unscrubbed private info
                                    </SelectItem>
                                    <SelectItem value="harassment">Harassment</SelectItem>
                                    <SelectItem value="spam">Spam</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1">
                            <Label>Details (optional)</Label>
                            <Textarea
                                value={data.details}
                                onChange={(e) => setData('details', e.target.value)}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Submit report
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RantShow({ rant, comments, isPreview }: Props) {
    const getInitials = useInitials();
    const user = usePage().props.auth?.user ?? null;

    const commentForm = useForm({ body: '', parent_id: null as number | null });

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

    const submitComment = (e: React.FormEvent) => {
        e.preventDefault();
        commentForm.post(`/rants/${rant.slug}/comments`, {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    };

    return (
        <>
            <Head title={rant.title} />

            {isPreview && (
                <div className="mb-4 rounded-lg border border-amber-400 bg-amber-50 px-4 py-2 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                    Moderator preview — this rant is <strong>not public</strong> yet.
                </div>
            )}

            <article className="rounded-2xl border bg-white p-6 dark:bg-neutral-900">
                <div className="mb-3 flex items-center gap-2 text-sm text-muted-foreground">
                    {rant.author ? (
                        <Link
                            href={`/@${rant.author.username}`}
                            className="flex items-center gap-2 hover:text-foreground"
                        >
                            <Avatar className="size-8">
                                <AvatarImage src={rant.author.avatar_url} />
                                <AvatarFallback>
                                    {getInitials(rant.author.name)}
                                </AvatarFallback>
                            </Avatar>
                            <span className="font-medium text-foreground">
                                {rant.author.name}
                            </span>
                            <span>@{rant.author.username}</span>
                        </Link>
                    ) : (
                        <span className="flex items-center gap-1 italic">
                            <Mail className="size-4" /> Anonymous emailer
                        </span>
                    )}
                    <Badge variant="secondary" className="ml-1 text-[10px]">
                        {rant.source_label}
                    </Badge>
                    <span className="ml-auto text-xs">{rant.published_human}</span>
                </div>

                <h1 className="text-2xl font-black sm:text-3xl">{rant.title}</h1>

                <div className="mt-4 whitespace-pre-wrap text-[15px] leading-relaxed">
                    {rant.body}
                </div>

                <div className="mt-6 border-t pt-4">
                    <ReactionBar slug={rant.slug} reactions={rant.reactions} />
                </div>

                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <Button
                        variant={rant.bookmarked ? 'default' : 'outline'}
                        size="sm"
                        onClick={toggleBookmark}
                        className={cn(
                            rant.bookmarked &&
                                'bg-yellow-300 text-black hover:bg-yellow-300/85',
                        )}
                    >
                        <Bookmark
                            className={cn('size-4', rant.bookmarked && 'fill-current')}
                        />
                        {rant.bookmarked ? 'Bookmarked' : 'Bookmark'} (
                        {rant.bookmarks_count})
                    </Button>
                    <ReportDialog slug={rant.slug} />
                    <span className="ml-auto text-xs text-muted-foreground">
                        {rant.view_count} reads
                    </span>
                </div>

                <div className="mt-4">
                    <ShareButtons title={rant.title} />
                </div>
            </article>

            <div className="mt-6">
                <ComebackGenerator slug={rant.slug} />
            </div>

            <section className="mt-8">
                <h2 className="mb-4 text-lg font-bold">Comments</h2>

                {user ? (
                    <form onSubmit={submitComment} className="mb-6 space-y-2">
                        <Textarea
                            value={commentForm.data.body}
                            onChange={(e) => commentForm.setData('body', e.target.value)}
                            placeholder="Pile on…"
                        />
                        <Button
                            type="submit"
                            disabled={
                                commentForm.processing || !commentForm.data.body.trim()
                            }
                        >
                            Post comment
                        </Button>
                    </form>
                ) : (
                    <p className="mb-6 rounded-lg border bg-white p-4 text-sm text-muted-foreground dark:bg-neutral-900">
                        <Link href="/login" className="font-medium text-foreground underline">
                            Log in
                        </Link>{' '}
                        to join the pile-on.
                    </p>
                )}

                <CommentThread comments={comments} rantSlug={rant.slug} />
            </section>
        </>
    );
}
