import { router } from '@inertiajs/react';
import { useConfirm } from '@/hooks/use-confirm';

/**
 * Demande confirmation puis supprime via Inertia (`preserveScroll`) — même
 * séquence répétée sur chaque liste admin (produits/marques/catégories).
 */
export function useConfirmDelete() {
    const confirm = useConfirm();

    return (message: string, url: string) => {
        void confirm(message, {
            title: 'Supprimer',
            confirmLabel: 'Supprimer',
            variant: 'destructive',
        }).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            router.delete(url, { preserveScroll: true });
        });
    };
}
