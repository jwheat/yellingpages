import { Head, Link } from '@inertiajs/react';
import Pagination from '@/components/site/pagination';
import RantCard from '@/components/site/rant-card';
import { cn } from '@/lib/utils';
import type { Paginated, RantCard as RantCardType } from '@/types/app';

type Props = {
    rants: Paginated<RantCardType>;
    sort: string;
};

const SORTS = [
    { key: 'latest', label: 'Latest' },
    { key: 'top', label: 'Most read' },
    { key: 'discussed', label: 'Most discussed' },
];

export default function RantIndex({ rants, sort }: Props) {
    return (
        <>
            <Head title="The Feed" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-black">The Feed</h1>
                <div className="flex gap-1 rounded-lg border bg-white p-1 dark:bg-neutral-900">
                    {SORTS.map((s) => (
                        <Link
                            key={s.key}
                            href={s.key === 'latest' ? '/rants' : `/rants?sort=${s.key}`}
                            className={cn(
                                'rounded-md px-3 py-1 text-sm font-medium transition',
                                sort === s.key
                                    ? 'bg-yellow-300 text-black'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {s.label}
                        </Link>
                    ))}
                </div>
            </div>

            {rants.data.length === 0 ? (
                <p className="py-16 text-center text-muted-foreground">
                    No rants yet. The calm before the storm.
                </p>
            ) : (
                <div className="grid gap-4">
                    {rants.data.map((rant) => (
                        <RantCard key={rant.id} rant={rant} />
                    ))}
                </div>
            )}

            <Pagination page={rants} />
        </>
    );
}
