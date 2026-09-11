import { Head, router } from '@inertiajs/react';
import Pagination from '@/components/site/pagination';
import RantCard from '@/components/site/rant-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import type { Paginated, RantCard as RantCardType } from '@/types/app';

type Profile = {
    name: string;
    username: string | null;
    bio: string | null;
    avatar_url: string;
    joined_human: string | null;
    rants_count: number;
    followers_count: number;
    following_count: number;
};

type Props = {
    profile: Profile;
    rants: Paginated<RantCardType>;
    isSelf: boolean;
    isFollowing: boolean;
};

export default function ProfileShow({ profile, rants, isSelf, isFollowing }: Props) {
    const getInitials = useInitials();

    const toggleFollow = () => {
        const url = `/users/${profile.username}/follow`;

        if (isFollowing) {
            router.delete(url, { preserveScroll: true });
        } else {
            router.post(url, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={`@${profile.username}`} />

            <div className="rounded-2xl border bg-white p-6 dark:bg-neutral-900">
                <div className="flex flex-wrap items-center gap-4">
                    <Avatar className="size-20 border-2 border-yellow-300">
                        <AvatarImage src={profile.avatar_url} />
                        <AvatarFallback>{getInitials(profile.name)}</AvatarFallback>
                    </Avatar>
                    <div className="flex-1">
                        <h1 className="text-2xl font-black">{profile.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            @{profile.username} · joined {profile.joined_human}
                        </p>
                        {profile.bio && (
                            <p className="mt-2 max-w-prose text-sm">{profile.bio}</p>
                        )}
                    </div>
                    {!isSelf && (
                        <Button
                            onClick={toggleFollow}
                            variant={isFollowing ? 'outline' : 'default'}
                            className={
                                isFollowing
                                    ? ''
                                    : 'bg-black text-yellow-300 hover:bg-black/85'
                            }
                        >
                            {isFollowing ? 'Following' : 'Follow'}
                        </Button>
                    )}
                </div>

                <div className="mt-5 flex gap-6 text-sm">
                    <span>
                        <strong>{profile.rants_count}</strong>{' '}
                        <span className="text-muted-foreground">rants</span>
                    </span>
                    <span>
                        <strong>{profile.followers_count}</strong>{' '}
                        <span className="text-muted-foreground">followers</span>
                    </span>
                    <span>
                        <strong>{profile.following_count}</strong>{' '}
                        <span className="text-muted-foreground">following</span>
                    </span>
                </div>
            </div>

            <h2 className="mt-8 mb-4 text-lg font-bold">Rants</h2>
            {rants.data.length === 0 ? (
                <p className="py-10 text-center text-muted-foreground">
                    No published rants yet.
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
