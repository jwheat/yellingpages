import { Head } from '@inertiajs/react';
import Pagination from '@/components/site/pagination';
import RantCard from '@/components/site/rant-card';
import type { Paginated, RantCard as RantCardType } from '@/types/app';

type Props = {
    rants: Paginated<RantCardType>;
};

export default function Bookmarks({ rants }: Props) {
    return (
        <>
            <Head title="Your bookmarks" />

            <h1 className="mb-6 text-2xl font-black">Your bookmarks</h1>

            {rants.data.length === 0 ? (
                <p className="py-16 text-center text-muted-foreground">
                    Nothing saved yet. Bookmark a rant to keep it handy.
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
