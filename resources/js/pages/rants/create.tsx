import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export default function CreateRant() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        body: '',
        terms: false as boolean,
        website: '', // honeypot
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/rants');
    };

    return (
        <>
            <Head title="Submit a rant" />

            <div className="mx-auto max-w-2xl">
                <h1 className="text-2xl font-black">Get it off your chest</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Write it out. We'll swap real names, companies, emails and phone
                    numbers for funny aliases, then a moderator approves it before it
                    goes live. Prefer email? Send it to{' '}
                    <span className="font-mono font-semibold">rant@yellingpages.com</span>.
                </p>

                <form
                    onSubmit={submit}
                    className="mt-6 space-y-5 rounded-2xl border bg-white p-6 dark:bg-neutral-900"
                >
                    <div className="space-y-1">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="My boss scheduled another 7am sync"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="body">Your rant</Label>
                        <Textarea
                            id="body"
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            className="min-h-48"
                            placeholder="Let it all out. Names and places will be scrubbed automatically."
                        />
                        <InputError message={errors.body} />
                    </div>

                    {/* Honeypot — hidden from real users */}
                    <div className="hidden" aria-hidden="true">
                        <label>
                            Website
                            <input
                                type="text"
                                tabIndex={-1}
                                autoComplete="off"
                                value={data.website}
                                onChange={(e) => setData('website', e.target.value)}
                            />
                        </label>
                    </div>

                    <div className="flex items-start gap-2">
                        <Checkbox
                            id="terms"
                            checked={data.terms}
                            onCheckedChange={(v) => setData('terms', v === true)}
                        />
                        <Label
                            htmlFor="terms"
                            className="text-sm font-normal leading-snug text-muted-foreground"
                        >
                            I have the right to post this, and it isn't defamatory or
                            doxxing anyone. I understand it will be edited for anonymity
                            and reviewed before publishing.
                        </Label>
                    </div>
                    <InputError message={errors.terms} />

                    <Button
                        type="submit"
                        disabled={processing}
                        className="bg-black text-yellow-300 hover:bg-black/85"
                    >
                        Submit for scrubbing
                    </Button>
                </form>
            </div>
        </>
    );
}
