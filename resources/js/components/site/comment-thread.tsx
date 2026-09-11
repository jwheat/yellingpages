import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { CommentNode } from '@/types/app';

function ReplyForm({
    rantSlug,
    parentId,
    onDone,
}: {
    rantSlug: string;
    parentId: number;
    onDone: () => void;
}) {
    const { data, setData, post, processing, reset } = useForm({
        body: '',
        parent_id: parentId as number | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/rants/${rantSlug}/comments`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onDone();
            },
        });
    };

    return (
        <form onSubmit={submit} className="mt-2 space-y-2">
            <Textarea
                value={data.body}
                onChange={(e) => setData('body', e.target.value)}
                placeholder="Add your two cents…"
                className="min-h-16"
                autoFocus
            />
            <div className="flex gap-2">
                <Button type="submit" size="sm" disabled={processing || !data.body.trim()}>
                    Reply
                </Button>
                <Button type="button" size="sm" variant="ghost" onClick={onDone}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}

function CommentItem({
    comment,
    rantSlug,
}: {
    comment: CommentNode;
    rantSlug: string;
}) {
    const getInitials = useInitials();
    const page = usePage();
    const user = page.props.auth?.user ?? null;
    const canModerate = page.props.auth?.canModerate ?? false;
    const [replying, setReplying] = useState(false);

    const canDelete =
        canModerate ||
        (user && comment.author?.username && user.username === comment.author.username);

    return (
        <div className="border-l-2 border-neutral-200 pl-3 dark:border-neutral-800">
            <div className="flex items-start gap-2">
                <Avatar className="size-7">
                    <AvatarImage src={comment.author?.avatar_url} />
                    <AvatarFallback>
                        {comment.author ? getInitials(comment.author.name) : '?'}
                    </AvatarFallback>
                </Avatar>
                <div className="flex-1">
                    <div className="text-sm">
                        <span className="font-semibold">
                            {comment.author?.name ?? 'Someone'}
                        </span>{' '}
                        <span className="text-xs text-muted-foreground">
                            {comment.created_human}
                        </span>
                    </div>
                    <p className="whitespace-pre-wrap text-sm">{comment.body}</p>
                    <div className="mt-1 flex gap-3 text-xs text-muted-foreground">
                        {user && (
                            <button
                                onClick={() => setReplying((v) => !v)}
                                className="hover:text-foreground"
                            >
                                Reply
                            </button>
                        )}
                        {canDelete && (
                            <button
                                onClick={() =>
                                    router.delete(`/comments/${comment.id}`, {
                                        preserveScroll: true,
                                    })
                                }
                                className="hover:text-destructive"
                            >
                                Delete
                            </button>
                        )}
                    </div>

                    {replying && (
                        <ReplyForm
                            rantSlug={rantSlug}
                            parentId={comment.id}
                            onDone={() => setReplying(false)}
                        />
                    )}
                </div>
            </div>

            {comment.replies.length > 0 && (
                <div className={cn('mt-3 space-y-3', 'ml-2')}>
                    {comment.replies.map((reply) => (
                        <CommentItem
                            key={reply.id}
                            comment={reply}
                            rantSlug={rantSlug}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function CommentThread({
    comments,
    rantSlug,
}: {
    comments: CommentNode[];
    rantSlug: string;
}) {
    if (comments.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                No comments yet. Be the first to pile on.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            {comments.map((comment) => (
                <CommentItem key={comment.id} comment={comment} rantSlug={rantSlug} />
            ))}
        </div>
    );
}
