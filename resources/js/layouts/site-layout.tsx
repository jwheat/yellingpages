import { Link, router, usePage } from '@inertiajs/react';
import { Bookmark, LogOut, PenLine, Settings, Shield, User as UserIcon } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';

type Flash = { success?: string | null; error?: string | null };

export default function SiteLayout({ children }: { children: React.ReactNode }) {
    const page = usePage();
    const auth = page.props.auth;
    const canModerate = auth?.canModerate ?? false;
    const user = auth?.user ?? null;
    const flash = (page.props.flash ?? {}) as Flash;
    const getInitials = useInitials();

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash.success, flash.error]);

    return (
        <div className="flex min-h-screen flex-col bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
            <header className="sticky top-0 z-40 border-b-2 border-black/80 bg-yellow-300 dark:border-yellow-400/30 dark:bg-yellow-500">
                <div className="mx-auto flex w-full max-w-5xl items-center gap-3 px-4 py-3">
                    <Link href="/" className="flex items-center gap-2 text-black">
                        <span className="flex size-9 items-center justify-center rounded-md bg-black text-lg text-yellow-300">
                            🖕
                        </span>
                        <span className="leading-none">
                            <span className="block text-lg font-black tracking-tight">
                                Yelling Pages
                            </span>
                            <span className="block text-[11px] font-medium text-black/70">
                                let your finger do the talking
                            </span>
                        </span>
                    </Link>

                    <nav className="ml-auto flex items-center gap-1 sm:gap-2">
                        <Button
                            asChild
                            variant="ghost"
                            className="text-black hover:bg-black/10"
                        >
                            <Link href="/rants">The Feed</Link>
                        </Button>

                        {user ? (
                            <>
                                <Button
                                    asChild
                                    className="bg-black text-yellow-300 hover:bg-black/85"
                                >
                                    <Link href="/rants/create">
                                        <PenLine className="size-4" /> Rant
                                    </Link>
                                </Button>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <button className="ml-1 rounded-full ring-black/10 transition hover:ring-4">
                                            <Avatar className="size-9 border-2 border-black/80">
                                                <AvatarImage src={user.avatar_url} alt={user.name} />
                                                <AvatarFallback>
                                                    {getInitials(user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-52">
                                        <DropdownMenuLabel>
                                            <div className="font-semibold">{user.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                @{user.username}
                                            </div>
                                        </DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        {user.username && (
                                            <DropdownMenuItem asChild>
                                                <Link href={`/@${user.username}`}>
                                                    <UserIcon className="size-4" /> My profile
                                                </Link>
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem asChild>
                                            <Link href="/bookmarks">
                                                <Bookmark className="size-4" /> Bookmarks
                                            </Link>
                                        </DropdownMenuItem>
                                        {canModerate && (
                                            <DropdownMenuItem asChild>
                                                <Link href="/moderation">
                                                    <Shield className="size-4" /> Moderation
                                                </Link>
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem asChild>
                                            <Link href="/settings/profile">
                                                <Settings className="size-4" /> Settings
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            onClick={() => router.post('/logout')}
                                        >
                                            <LogOut className="size-4" /> Log out
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </>
                        ) : (
                            <>
                                <Button
                                    asChild
                                    variant="ghost"
                                    className="text-black hover:bg-black/10"
                                >
                                    <Link href="/login">Log in</Link>
                                </Button>
                                <Button
                                    asChild
                                    className="bg-black text-yellow-300 hover:bg-black/85"
                                >
                                    <Link href="/register">Sign up</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6">{children}</main>

            <footer className="border-t border-neutral-200 bg-white py-8 text-center text-sm text-neutral-500 dark:border-neutral-800 dark:bg-neutral-900">
                <p className="font-semibold text-neutral-700 dark:text-neutral-300">
                    Yelling Pages — let your finger do the talking 🖕
                </p>
                <p className="mx-auto mt-2 max-w-xl px-4 text-xs">
                    A parody site. Names and places are swapped before posting. Anything
                    resembling a real person or company is, legally speaking, a coincidence.
                </p>
            </footer>
        </div>
    );
}
