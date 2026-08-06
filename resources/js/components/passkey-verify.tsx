import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    routes?: {
        options: UrlMethodPair;
        submit: UrlMethodPair;
    };
    label?: string;
    loadingLabel?: string;
    separator?: string;
};

const ERROR_MESSAGES: Record<string, string> = {
    NotSupportedError:
        'Les passkeys ne sont pas prises en charge par ce navigateur.',
    UserCancelledError: 'Opération annulée.',
    PasskeyExistsError: 'Cet appareil est déjà enregistré comme passkey.',
    InvalidDomainError: "Ce domaine n'est pas autorisé pour les passkeys.",
};

function translateError(errorInstance: Error | null, fallback: string | null) {
    if (!errorInstance) {
        return fallback;
    }

    return (
        ERROR_MESSAGES[errorInstance.name] ??
        "Une erreur est survenue lors de l'authentification par passkey."
    );
}

export default function PasskeyVerify({
    routes,
    label,
    loadingLabel,
    separator,
}: Props = {}) {
    const { verify, isLoading, error, errorInstance, isSupported } =
        usePasskeyVerify({
            ...(routes && {
                routes: {
                    options: routes.options.url,
                    submit: routes.submit.url,
                },
            }),
            onSuccess: (response) => {
                router.visit(response.redirect ?? '/dashboard');
            },
        });

    const translatedError = translateError(errorInstance, error);

    if (!isSupported) {
        return null;
    }

    return (
        <>
            <div className="grid gap-2">
                <Button
                    type="button"
                    variant="outline"
                    className="w-full"
                    onClick={verify}
                    disabled={isLoading}
                >
                    {isLoading ? <Spinner /> : <KeyRound className="h-4 w-4" />}
                    {isLoading
                        ? (loadingLabel ?? 'Authentification...')
                        : (label ?? 'Se connecter avec une passkey')}
                </Button>
                {translatedError && (
                    <InputError
                        message={translatedError}
                        className="text-center"
                    />
                )}
            </div>

            <div className="relative my-6">
                <div className="absolute inset-0 flex items-center">
                    <Separator className="w-full" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-background px-2 text-muted-foreground">
                        {separator ?? 'Ou continuer avec un email'}
                    </span>
                </div>
            </div>
        </>
    );
}
