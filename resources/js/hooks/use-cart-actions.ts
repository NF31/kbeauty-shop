import { router } from '@inertiajs/react';
import { useCartStore } from '@/stores/cart-store';

/**
 * Actions panier partagées entre la page /panier et le mini-panier du
 * header : mutation optimiste du store avant l'appel serveur, resynchronisée
 * ensuite via la réponse Inertia (cf. store.sync()).
 */
export function useCartActions() {
    const setQuantityOptimistic = useCartStore(
        (state) => state.setQuantityOptimistic,
    );
    const removeOptimistic = useCartStore((state) => state.removeOptimistic);

    return {
        updateQuantity(itemId: number, quantity: number) {
            setQuantityOptimistic(itemId, quantity);
            router.patch(
                `/panier/${itemId}`,
                { quantity },
                { preserveScroll: true, preserveState: true },
            );
        },
        removeItem(itemId: number) {
            removeOptimistic(itemId);
            router.delete(`/panier/${itemId}`, { preserveScroll: true });
        },
    };
}
