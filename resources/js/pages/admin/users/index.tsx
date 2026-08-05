import { Form, Head } from '@inertiajs/react';
import { DataList } from '@/components/admin/data-list';
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
};

const roleOptions = [
    { value: 'admin', label: 'Admin' },
    { value: 'staff', label: 'Staff' },
    { value: 'support', label: 'Support' },
];

export default function UsersIndex({ users }: UsersIndexProps) {
    return (
        <>
            <Head title="Utilisateurs" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Utilisateurs"
                    description="Rôle admin, staff ou support de chaque compte."
                />

                <DataList
                    rows={users.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucun utilisateur."
                    renderRow={(user) => (
                        <Form
                            {...admin.users.updateRole.form(user.id)}
                            className="flex flex-wrap items-center justify-between gap-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {user.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
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
