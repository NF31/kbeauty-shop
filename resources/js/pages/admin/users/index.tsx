import { Form, Head } from '@inertiajs/react';
import { DataList } from '@/components/admin/data-list';
import { ListFilters } from '@/components/admin/list-filters';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string | null;
};

type UsersIndexProps = {
    users: Paginated<UserRow>;
    filters: { search: string };
};

const roleOptions = [
    { value: 'admin', label: 'Admin' },
    { value: 'staff', label: 'Staff' },
    { value: 'support', label: 'Support' },
];

export default function UsersIndex({ users, filters }: UsersIndexProps) {
    return (
        <>
            <Head title="Utilisateurs" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Utilisateurs"
                    description="Rôle admin, staff ou support de chaque compte."
                />

                <ListFilters
                    search={filters.search}
                    searchPlaceholder="Rechercher un utilisateur…"
                />

                <DataList
                    rows={users.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucun utilisateur."
                    renderRow={(user) => (
                        <Form
                            {...admin.users.updateRole.form(user.id)}
                            className="grid grid-cols-1 items-center gap-3 sm:grid-cols-[minmax(0,1fr)_auto]"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="min-w-0 overflow-hidden">
                                        <p className="font-medium break-words">
                                            {user.name}
                                        </p>
                                        <p className="text-sm break-words text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Select
                                            name="role"
                                            defaultValue={
                                                user.role ?? undefined
                                            }
                                        >
                                            <SelectTrigger className="w-36">
                                                <SelectValue placeholder="Aucun rôle" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {roleOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            Enregistrer
                                        </Button>
                                    </div>
                                    <InputError
                                        message={errors.role}
                                        className="w-full"
                                    />
                                </>
                            )}
                        </Form>
                    )}
                />

                <Pagination links={users.links} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Utilisateurs', href: admin.users.index() },
    ],
};
