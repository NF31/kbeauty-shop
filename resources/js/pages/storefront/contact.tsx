import { zodResolver } from '@hookform/resolvers/zod';
import { router, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { PageHeading } from '@/components/storefront/page-heading';
import { SeoHead } from '@/components/storefront/seo-head';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { localizedPath } from '@/lib/locale-path';
import contact from '@/routes/storefront/contact';

const contactSchema = z.object({
    name: z.string().min(1, 'Le nom est requis.'),
    email: z.string().email('Adresse email invalide.'),
    subject: z.string().min(1, 'Le sujet est requis.'),
    message: z.string().min(1, 'Le message est requis.').max(5000),
});

type ContactFormValues = z.infer<typeof contactSchema>;

export default function ContactPage() {
    const { t } = useLaravelReactI18n();
    const { auth, locale, honeypot } = usePage().props;

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            { title: t('Contact'), href: '#' },
        ],
    });

    const {
        register,
        handleSubmit,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<ContactFormValues>({
        resolver: zodResolver(contactSchema),
        defaultValues: {
            name: auth.user?.name ?? '',
            email: auth.user?.email ?? '',
            subject: '',
            message: '',
        },
    });

    const onSubmit = (values: ContactFormValues) => {
        router.post(
            contact.store().url,
            {
                ...values,
                [honeypot.nameFieldName]: '',
                [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
            },
            {
                preserveScroll: true,
                onSuccess: () =>
                    reset({
                        name: auth.user?.name ?? '',
                        email: auth.user?.email ?? '',
                        subject: '',
                        message: '',
                    }),
                onError: () =>
                    toast.error(
                        t(
                            "Impossible d'envoyer le message, réessaie dans quelques instants.",
                        ),
                    ),
            },
        );
    };

    return (
        <>
            <SeoHead
                title={t('Contact')}
                description={t(
                    'Une question sur un produit ou une commande ? Contacte notre équipe.',
                )}
            />
            <div className="mx-auto max-w-2xl space-y-6 p-4 md:p-8">
                <PageHeading
                    title={t('Contact')}
                    description={t(
                        'Une question, une suggestion ? Écris-nous, nous te répondrons rapidement.',
                    )}
                />

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="name">{t('Nom')}</Label>
                            <Input id="name" {...register('name')} />
                            {errors.name && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.name.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="email">{t('Adresse email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                {...register('email')}
                            />
                            {errors.email && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.email.message}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="subject">{t('Sujet')}</Label>
                        <Input id="subject" {...register('subject')} />
                        {errors.subject && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.subject.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="message">{t('Message')}</Label>
                        <Textarea
                            id="message"
                            rows={6}
                            {...register('message')}
                        />
                        {errors.message && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.message.message}
                            </p>
                        )}
                    </div>

                    <Button type="submit" disabled={isSubmitting}>
                        {t('Envoyer')}
                    </Button>
                </form>
            </div>
        </>
    );
}
