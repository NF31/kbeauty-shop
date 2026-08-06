import { useSyncExternalStore } from 'react';

const MOBILE_BREAKPOINT = 768;

const mql =
    typeof window === 'undefined'
        ? undefined
        : window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);

function mediaQueryListener(callback: (event: MediaQueryListEvent) => void) {
    if (!mql) {
        return () => {};
    }

    mql.addEventListener('change', callback);

    return () => {
        mql.removeEventListener('change', callback);
    };
}

function isSmallerThanBreakpoint(): boolean {
    return mql?.matches ?? false;
}

function getServerSnapshot(): boolean {
    return false;
}

/** `useSyncExternalStore` plutôt que useState+useEffect : `mql` (MediaQueryList) est déjà une source externe vivante, pas besoin de dupliquer son état dans un state React ni de gérer l'abonnement manuellement dans un effect. */
export function useIsMobile(): boolean {
    return useSyncExternalStore(
        mediaQueryListener,
        isSmallerThanBreakpoint,
        getServerSnapshot,
    );
}
