import { Head, Link } from '@inertiajs/react';
import { DataList } from '@/components/admin/data-list';
import { PageHeader } from '@/components/admin/page-header';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import admin from '@/routes/admin';

type ContactMessageRow = {
    id: number;
    name: string;
    email: string;
    subject: string;
    isRead: boolean;
    isReplied: boolean;
    createdAt: string | null;
};

type ContactMessagesIndexProps = {
    messages: Paginated<ContactMessageRow>;
    unreadCount: number;
};

export default function ContactMessagesIndex({
    messages,
    unreadCount,
}: ContactMessagesIndexProps) {
    return (
        <>
            <Head title="Messages" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Messages"
                    description={
                        unreadCount > 0
                            ? `${unreadCount} message${unreadCount > 1 ? 's' : ''} non lu${unreadCount > 1 ? 's' : ''}.`
                            : 'Tous les messages ont été lus.'
                    }
                />

                <DataList
                    rows={messages.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucun message pour l'instant."
                    renderRow={(message) => (
                        <Link
                            href={admin.contactMessages.show(message.id)}
                            className="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2">
                                    {!message.isRead && (
                                        <Badge variant="default">Non lu</Badge>
                                    )}
                                    {message.isReplied && (
                                        <Badge variant="secondary">
                                            Répondu
                                        </Badge>
                                    )}
                                    <p className="min-w-0 font-medium break-words">
                                        {message.subject}
                                    </p>
                                </div>
                                <p className="text-sm break-words text-muted-foreground">
                                    {message.name} · {message.email}
                                </p>
                            </div>
                            <span className="shrink-0 text-sm text-muted-foreground">
                                {message.createdAt
                                    ? new Date(
                                          message.createdAt,
                                      ).toLocaleString('fr-FR')
                                    : '—'}
                            </span>
                        </Link>
                    )}
                />

                <Pagination links={messages.links} />
            </div>
        </>
    );
}

ContactMessagesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Messages', href: admin.contactMessages.index() },
    ],
};
