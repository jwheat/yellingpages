import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ModerationRant } from '@/types/app';

type Props = {
    pending: ModerationRant[];
    reported: ModerationRant[];
};

function ScrubDiff({ rant }: { rant: ModerationRant }) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <div>
                <p className="mb-1 text-xs font-semibold uppercase text-muted-foreground">
                    Original (private)
                </p>
                <div className="max-h-56 overflow-auto rounded-md border bg-red-50 p-3 text-sm whitespace-pre-wrap dark:bg-red-950/30">
                    {rant.original_body}
                </div>
            </div>
            <div>
                <p className="mb-1 text-xs font-semibold uppercase text-muted-foreground">
                    Scrubbed (public)
                </p>
                <div className="max-h-56 overflow-auto rounded-md border bg-green-50 p-3 text-sm whitespace-pre-wrap dark:bg-green-950/30">
                    {rant.scrubbed_body ?? (
                        <span className="italic text-muted-foreground">
                            Not scrubbed yet…
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}

function RantBlock({
    rant,
    actions,
}: {
    rant: ModerationRant;
    actions: React.ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <CardTitle className="text-base">{rant.title}</CardTitle>
                    {rant.needs_review && (
                        <Badge variant="destructive">needs review</Badge>
                    )}
                    <Badge variant="secondary">{rant.source}</Badge>
                    {rant.reports_count != null && (
                        <Badge variant="destructive">
                            {rant.reports_count} reports
                        </Badge>
                    )}
                    <span className="ml-auto text-xs text-muted-foreground">
                        {rant.author ? `@${rant.author.username}` : 'anonymous'} ·{' '}
                        {rant.created_human}
                    </span>
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                <ScrubDiff rant={rant} />
                {rant.scrub_report && rant.scrub_report.changes.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {rant.scrub_report.changes.map((c, i) => (
                            <Badge key={i} variant="outline" className="text-[10px]">
                                {c.type}: {c.alias} ×{c.count}
                            </Badge>
                        ))}
                    </div>
                )}
            </CardContent>
            <CardFooter className="gap-2">{actions}</CardFooter>
        </Card>
    );
}

export default function ModerationIndex({ pending, reported }: Props) {
    const approve = (slug: string) =>
        router.post(`/moderation/${slug}/approve`, {}, { preserveScroll: true });
    const reject = (slug: string) =>
        router.post(`/moderation/${slug}/reject`, {}, { preserveScroll: true });
    const remove = (slug: string) =>
        router.post(`/moderation/${slug}/remove`, {}, { preserveScroll: true });

    return (
        <>
            <Head title="Moderation" />

            <div className="space-y-8 p-4">
                <section>
                    <h1 className="mb-1 text-2xl font-black">Moderation queue</h1>
                    <p className="mb-4 text-sm text-muted-foreground">
                        Review the scrub, then publish or reject. Nothing is public until
                        you approve it.
                    </p>

                    {pending.length === 0 ? (
                        <p className="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                            Queue's empty. Nicely done.
                        </p>
                    ) : (
                        <div className="space-y-4">
                            {pending.map((rant) => (
                                <RantBlock
                                    key={rant.id}
                                    rant={rant}
                                    actions={
                                        <>
                                            <Button
                                                onClick={() => approve(rant.slug)}
                                                disabled={!rant.scrubbed_body}
                                            >
                                                Approve & publish
                                            </Button>
                                            <Button
                                                variant="outline"
                                                onClick={() => reject(rant.slug)}
                                            >
                                                Reject
                                            </Button>
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={`/rants/${rant.slug}`}>
                                                    Preview
                                                </Link>
                                            </Button>
                                        </>
                                    }
                                />
                            ))}
                        </div>
                    )}
                </section>

                {reported.length > 0 && (
                    <section>
                        <h2 className="mb-4 text-xl font-bold">Reported rants</h2>
                        <div className="space-y-4">
                            {reported.map((rant) => (
                                <RantBlock
                                    key={rant.id}
                                    rant={rant}
                                    actions={
                                        <>
                                            <Button
                                                variant="destructive"
                                                onClick={() => remove(rant.slug)}
                                            >
                                                Remove from site
                                            </Button>
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={`/rants/${rant.slug}`}>
                                                    View
                                                </Link>
                                            </Button>
                                        </>
                                    }
                                />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}
