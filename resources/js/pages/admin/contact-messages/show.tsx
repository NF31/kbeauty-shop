import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import admin from '@/routes/admin';

type ContactMessageReplyRow = {
    id: number;
    message: string;
    authorName: string;
    createdAt: string | null;
};

type ContactMessageShowProps = {
    message: {
        id: number;
        name: string;
        email: string;
        subject: string;
        message: string;
        readAt: string | null;
        repliedAt: string | null;
        createdAt: string | null;
    };
    replies: ContactMessageReplyRow[];
};

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('fr-FR') : '—';
}

export default function ContactMessageShow({
    message,
    replies,
}: ContactMessageShowProps) {
    const [reply, setReply] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleReply = (event: React.FormEvent) => {
        event.preventDefault();
        setIsSubmitting(true);

        router.post(
            admin.contactMessages.reply(message.id).url,
            { reply },
            {
                preserveScroll: true,
                onSuccess: () => setReply(''),
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const details: { label: string; value: string }[] = [
        { label: 'N° de message', value: `#${message.id}` },
        { label: 'Nom', value: message.name },
        { label: 'Email', value: message.email },
        { label: 'Reçu le', value: formatDateTime(message.createdAt) },
        {
            label: 'Lu',
            value: message.readAt
                ? `Oui, le ${formatDateTime(message.readAt)}`
                : 'Non',
        },
        {
            label: 'Répondu',
            value: message.repliedAt
                ? `Oui, le ${formatDateTime(message.repliedAt)}`
                : 'Non',
        },
    ];

    return (
        <>
            <Head title={message.subject} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title={message.subject}
                    description={`De ${message.name} (${message.email}) · ${formatDateTime(message.createdAt)}`}
                    actions={
                        <Button variant="outline" asChild>
                            <a
                                href={`mailto:${message.email}?subject=${encodeURIComponent('Re: ' + message.subject)}`}
                            >
                                Ouvrir dans le client mail
                            </a>
                        </Button>
                    }
                />

                <Card>
                    <CardContent className="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                        {details.map((detail) => (
                            <div key={detail.label} className="min-w-0">
                                <p className="text-xs text-muted-foreground">
                                    {detail.label}
                                </p>
                                <p className="text-sm font-medium break-words">
                                    {detail.value}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="text-sm leading-relaxed whitespace-pre-wrap">
                        {message.message}
                    </CardContent>
                </Card>

                {replies.length > 0 && (
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <h2 className="font-semibold">Réponses envoyées</h2>
                            <ul className="flex flex-col gap-3">
                                {replies.map((reply) => (
                                    <li
                                        key={reply.id}
                                        className="rounded-lg border bg-muted/40 p-3"
                                    >
                                        <div className="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                                            <span>{reply.authorName}</span>
                                            <span>
                                                {formatDateTime(
                                                    reply.createdAt,
                                                )}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-sm leading-relaxed whitespace-pre-wrap">
                                            {reply.message}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="flex flex-col gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="font-semibold">Répondre</h2>
                            {message.repliedAt && (
                                <Badge variant="secondary">
                                    Déjà répondu le{' '}
                                    {formatDateTime(message.repliedAt)}
                                </Badge>
                            )}
                        </div>

                        <form
                            onSubmit={handleReply}
                            className="flex flex-col gap-3"
                        >
                            <div>
                                <Label htmlFor="reply" className="sr-only">
                                    Réponse
                                </Label>
                                <Textarea
                                    id="reply"
                                    rows={6}
                                    required
                                    placeholder={`Écris ta réponse à ${message.name}…`}
                                    value={reply}
                                    onChange={(e) => setReply(e.target.value)}
                                />
                            </div>
                            <Button
                                type="submit"
                                className="self-start"
                                disabled={isSubmitting || reply.trim() === ''}
                            >
                                Envoyer la réponse par email
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ContactMessageShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Messages', href: admin.contactMessages.index() },
        { title: 'Message', href: '#' },
    ],
};
