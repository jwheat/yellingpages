import { Link, usePage } from '@inertiajs/react';
import { Copy, Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

const TONES = [
    { key: 'savage', label: '🔪 Savage' },
    { key: 'petty', label: '💅 Petty' },
    { key: 'corporate', label: '👔 Corporate' },
    { key: 'unhinged', label: '🤪 Unhinged' },
];

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export default function ComebackGenerator({ slug }: { slug: string }) {
    const user = usePage().props.auth?.user ?? null;
    const [tone, setTone] = useState('savage');
    const [loading, setLoading] = useState(false);
    const [comeback, setComeback] = useState<string | null>(null);
    const [, copy] = useClipboard();

    const generate = async () => {
        setLoading(true);

        try {
            const res = await fetch(`/rants/${slug}/comeback`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ tone }),
            });

            if (res.status === 429) {
                toast.error('Whoa, easy. Give it a minute.');

                return;
            }

            if (!res.ok) {
                toast.error('Could not write a comeback. Try again.');

                return;
            }

            const data = await res.json();
            setComeback(data.comeback);
        } catch {
            toast.error('Something went wrong.');
        } finally {
            setLoading(false);
        }
    };

    const copyComeback = async () => {
        if (comeback && (await copy(comeback))) {
            toast.success('Comeback copied. Use it wisely.');
        }
    };

    return (
        <Card className="border-yellow-300">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Sparkles className="size-5 text-yellow-500" />
                    The Comeback Generator
                </CardTitle>
                <CardDescription>
                    The reply email you wish you could send. Pick a tone.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {user ? (
                    <>
                        <div className="flex flex-wrap gap-2">
                            {TONES.map((t) => (
                                <button
                                    key={t.key}
                                    onClick={() => setTone(t.key)}
                                    className={cn(
                                        'rounded-full border px-3 py-1 text-sm transition',
                                        tone === t.key
                                            ? 'border-yellow-400 bg-yellow-300 text-black'
                                            : 'border-neutral-200 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800',
                                    )}
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>

                        <Button
                            onClick={generate}
                            disabled={loading}
                            className="bg-black text-yellow-300 hover:bg-black/85"
                        >
                            {loading ? (
                                <>
                                    <Loader2 className="size-4 animate-spin" /> Channeling
                                    your rage…
                                </>
                            ) : comeback ? (
                                'Write another'
                            ) : (
                                'Write my comeback'
                            )}
                        </Button>

                        {comeback && (
                            <div className="relative rounded-lg border bg-neutral-50 p-4 dark:bg-neutral-950">
                                <pre className="font-sans text-sm leading-relaxed whitespace-pre-wrap">
                                    {comeback}
                                </pre>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={copyComeback}
                                    className="mt-3"
                                >
                                    <Copy className="size-4" /> Copy
                                </Button>
                            </div>
                        )}
                    </>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        <Link
                            href="/login"
                            className="font-medium text-foreground underline"
                        >
                            Log in
                        </Link>{' '}
                        to generate the email you'll never actually send.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
