import { router, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { ReactionSummary } from '@/types/app';

// Keep in sync with App\Enums\ReactionType.
export const REACTIONS: { key: string; emoji: string; label: string }[] = [
    { key: 'fire', emoji: '🔥', label: 'This is fire' },
    { key: 'rage', emoji: '😤', label: 'Pure rage' },
    { key: 'skull', emoji: '💀', label: 'I am deceased' },
    { key: 'lol', emoji: '😂', label: 'Lol' },
    { key: 'oof', emoji: '🫠', label: 'Relatable pain' },
];

export default function ReactionBar({
    slug,
    reactions,
}: {
    slug: string;
    reactions: ReactionSummary;
}) {
    const user = usePage().props.auth?.user ?? null;

    const react = (type: string) => {
        if (!user) {
            router.visit('/login');

            return;
        }

        router.post(
            `/rants/${slug}/reactions`,
            { type },
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex flex-wrap items-center gap-1.5">
            {REACTIONS.map(({ key, emoji, label }) => {
                const count = reactions.counts[key] ?? 0;
                const mine = reactions.mine === key;

                return (
                    <button
                        key={key}
                        onClick={() => react(key)}
                        title={label}
                        aria-pressed={mine}
                        className={cn(
                            'flex items-center gap-1 rounded-full border px-2.5 py-1 text-sm transition',
                            mine
                                ? 'border-yellow-400 bg-yellow-300 text-black'
                                : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:hover:bg-neutral-800',
                        )}
                    >
                        <span className="text-base leading-none">{emoji}</span>
                        {count > 0 && (
                            <span className="text-xs font-semibold tabular-nums">
                                {count}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
