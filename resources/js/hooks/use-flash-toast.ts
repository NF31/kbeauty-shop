import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            // id stable : en dev, React.StrictMode (createInertiaApp
            // strictMode: true) double-invoque l'effet au montage, ce qui
            // peut declencher deux abonnements a l'evenement 'flash' avant
            // que le cleanup du premier ne s'applique. Avec le meme id,
            // sonner remplace le toast existant au lieu d'en empiler un
            // second.
            toast[data.type](data.message, { id: 'flash-toast' });
        });
    }, []);
}
