import { router } from '@inertiajs/react';

/**
 * Demande confirmation puis supprime via Inertia (`preserveScroll`) — même
 * séquence répétée sur chaque liste admin (produits/marques/catégories).
 */
export function useConfirmDelete() {
    return (message: string, url: string) => {
        if (!confirm(message)) {
            return;
        }

        router.delete(url, { preserveScroll: true });
    };
}
