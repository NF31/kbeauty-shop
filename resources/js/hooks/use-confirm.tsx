import type { PropsWithChildren } from 'react';
import {
    createContext,
    useCallback,
    useContext,
    useRef,
    useState,
} from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

type ConfirmOptions = {
    title?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'destructive';
};

type ConfirmState = ConfirmOptions & {
    message: string;
};

type ConfirmFn = (
    message: string,
    options?: ConfirmOptions,
) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);

/**
 * Remplace `window.confirm` par une AlertDialog stylee (coherente avec le
 * reste de l'UI shadcn/sonner) — montee une seule fois a la racine (app.tsx),
 * pilotee via `useConfirm()` depuis n'importe quelle page.
 */
export function ConfirmProvider({ children }: PropsWithChildren) {
    const [state, setState] = useState<ConfirmState | null>(null);
    const resolveRef = useRef<((value: boolean) => void) | null>(null);

    const confirm = useCallback<ConfirmFn>((message, options) => {
        setState({ message, ...options });

        return new Promise<boolean>((resolve) => {
            resolveRef.current = resolve;
        });
    }, []);

    const settle = (value: boolean) => {
        resolveRef.current?.(value);
        resolveRef.current = null;
        setState(null);
    };

    return (
        <ConfirmContext.Provider value={confirm}>
            {children}
            <AlertDialog
                open={state !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        settle(false);
                    }
                }}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {state?.title ?? 'Confirmer'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {state?.message}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => settle(false)}>
                            {state?.cancelLabel ?? 'Annuler'}
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => settle(true)}
                            className={
                                state?.variant === 'destructive'
                                    ? 'bg-destructive text-white hover:bg-destructive/90'
                                    : undefined
                            }
                        >
                            {state?.confirmLabel ?? 'Confirmer'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </ConfirmContext.Provider>
    );
}

export function useConfirm(): ConfirmFn {
    const confirm = useContext(ConfirmContext);

    if (!confirm) {
        throw new Error('useConfirm must be used within a ConfirmProvider');
    }

    return confirm;
}
