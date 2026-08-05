import { router, usePage } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { destroy, store } from '@/routes/storefront/wishlist';

type WishlistButtonProps = {
    productSlug: string;
    isWishlisted: boolean;
    className?: string;
};

/**
 * Etat optimiste local : le coeur bascule immediatement au clic, sans
 * attendre la reponse serveur (meme pattern que use-cart-actions.ts) - la
 * wishlist n'a pas de store Zustand partage, ce composant est autonome.
 */
export function WishlistButton({
    productSlug,
    isWishlisted,
    className,
}: WishlistButtonProps) {
    const { auth } = usePage().props;
    const [wishlisted, setWishlisted] = useState(isWishlisted);
    const [isPending, setIsPending] = useState(false);

    if (!auth.user) {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className={cn('shrink-0', className)}
                        aria-label="Se connecter pour ajouter aux favoris"
                        asChild
                    >
                        <a href={login().url}>
                            <Heart className="size-5" />
                        </a>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    Connecte-toi pour ajouter ce produit à tes favoris
                </TooltipContent>
            </Tooltip>
        );
    }

    const toggle = () => {
        const next = !wishlisted;
        setWishlisted(next);
        setIsPending(true);

        const options = {
            preserveScroll: true,
            onError: () => setWishlisted(!next),
            onFinish: () => setIsPending(false),
        } as const;

        if (next) {
            router.post(store(productSlug).url, {}, options);
        } else {
            router.delete(destroy(productSlug).url, options);
        }
    };

    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            className={cn('shrink-0', className)}
            disabled={isPending}
            onClick={toggle}
            aria-label={
                wishlisted ? 'Retirer des favoris' : 'Ajouter aux favoris'
            }
            aria-pressed={wishlisted}
        >
            <Heart
                className={cn(
                    'size-5',
                    wishlisted && 'fill-destructive text-destructive',
                )}
            />
        </Button>
    );
}
