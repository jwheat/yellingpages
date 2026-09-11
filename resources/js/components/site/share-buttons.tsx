import { Check, Link2, Mail, Share2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';

export default function ShareButtons({ title }: { title: string }) {
    const [url] = useState(() =>
        typeof window !== 'undefined' ? window.location.href : '',
    );
    const [copied, copy] = useClipboard();

    const shareText = encodeURIComponent(`"${title}" on Yelling Pages`);
    const shareUrl = encodeURIComponent(url);

    const copyLink = async () => {
        if (await copy(url)) {
            toast.success('Link copied to clipboard');
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="flex items-center gap-1 text-sm text-muted-foreground">
                <Share2 className="size-4" /> Share:
            </span>
            <Button variant="outline" size="sm" onClick={copyLink}>
                {copied ? <Check className="size-4" /> : <Link2 className="size-4" />}
                Copy link
            </Button>
            <Button variant="outline" size="sm" asChild>
                <a
                    href={`https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    X / Twitter
                </a>
            </Button>
            <Button variant="outline" size="sm" asChild>
                <a
                    href={`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Facebook
                </a>
            </Button>
            <Button variant="outline" size="sm" asChild>
                <a href={`mailto:?subject=${shareText}&body=${shareUrl}`}>
                    <Mail className="size-4" /> Email
                </a>
            </Button>
        </div>
    );
}
