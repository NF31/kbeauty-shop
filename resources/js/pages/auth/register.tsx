import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { NoIndexHead } from '@/components/no-index-head';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { TurnstileWidget } from '@/components/turnstile-widget';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

const TURNSTILE_REQUIRED = Boolean(import.meta.env.VITE_TURNSTILE_SITE_KEY);

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const [turnstileToken, setTurnstileToken] = useState('');

    return (
        <>
            <NoIndexHead title="Inscription" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Nom complet"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Adresse email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@exemple.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Mot de passe</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Mot de passe"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmer le mot de passe
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirmer le mot de passe"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <input
                                type="hidden"
                                name="cf-turnstile-response"
                                value={turnstileToken}
                            />
                            <TurnstileWidget
                                onVerify={setTurnstileToken}
                                onExpire={() => setTurnstileToken('')}
                            />
                            <InputError
                                message={errors['cf-turnstile-response']}
                            />

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                                disabled={
                                    processing ||
                                    (TURNSTILE_REQUIRED && !turnstileToken)
                                }
                            >
                                {processing && <Spinner />}
                                Créer un compte
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Déjà un compte ?{' '}
                            <TextLink href={login()} tabIndex={6}>
                                Se connecter
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Créer un compte',
    description: 'Entrez vos informations pour créer votre compte',
};
