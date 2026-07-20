import { Head } from '@inertiajs/react';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import admin from '@/routes/admin';

type DashboardProps = {
    stats: {
        productsCount: number;
        publishedProductsCount: number;
        lowStockVariantsCount: number;
    };
};

export default function AdminDashboard({ stats }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard admin" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>Produits</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.productsCount}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Produits publiés</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.publishedProductsCount}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>
                                Variantes en stock bas
                            </CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.lowStockVariantsCount}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: admin.dashboard(),
        },
    ],
};
