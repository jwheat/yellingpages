import { Head, Link } from '@inertiajs/react';
import RantCard from '@/components/site/rant-card';
import { Button } from '@/components/ui/button';
import type { RantCard as RantCardType } from '@/types/app';

type Props = {
    latest: RantCardType[];
    stats: { rants: number; comments: number };
};

export default function Welcome({ latest, stats }: Props) {
    return (
        <>
            <Head title="Let your finger do the talking" />

            <section className="rounded-2xl border-2 border-black/80 bg-yellow-300 px-6 py-12 text-center text-black shadow-[6px_6px_0_0_rgba(0,0,0,0.85)] dark:border-yellow-400/30">
                <p className="text-6xl">🖕</p>
                <h1 className="mt-3 text-4xl font-black tracking-tight sm:text-5xl">
                    Yelling Pages
                </h1>
                <p className="mt-2 text-lg font-semibold">
                    Let your finger do the talking.
                </p>
                <p className="mx-auto mt-4 max-w-xl text-sm text-black/80">
                    Got a workplace rant you can't send? Email it to{' '}
                    <span className="font-mono font-bold">rant@yellingpages.com</span>.
                    We swap the names and places to protect the guilty, then post it for
                    the world to read.
                </p>
                <div className="mt-6 flex flex-wrap justify-center gap-3">
                    <Button asChild className="bg-black text-yellow-300 hover:bg-black/85">
                        <Link href="/rants">Read the rants</Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="border-black bg-transparent text-black hover:bg-black/10"
                    >
                        <Link href="/rants/create">Submit your own</Link>
                    </Button>
                </div>
                <p className="mt-6 text-xs font-medium text-black/70">
                    {stats.rants} rants posted · {stats.comments} comments and counting
                </p>
            </section>

            <section className="mt-10">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-xl font-bold">Fresh off the press</h2>
                    <Link
                        href="/rants"
                        className="text-sm text-muted-foreground hover:text-foreground"
                    >
                        See all →
                    </Link>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    {latest.map((rant) => (
                        <RantCard key={rant.id} rant={rant} />
                    ))}
                </div>
            </section>
        </>
    );
}
