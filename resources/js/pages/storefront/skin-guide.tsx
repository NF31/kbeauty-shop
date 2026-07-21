import { Head, Link } from '@inertiajs/react';

type SkinTypeOption = { value: string; label: string };

type SkinGuidePageProps = {
    skinTypeOptions: SkinTypeOption[];
};

export default function SkinGuidePage({ skinTypeOptions }: SkinGuidePageProps) {
    return (
        <>
            <Head title="Guide de choix" />
            <div className="mx-auto max-w-xl p-4 md:p-8">
                <h1 className="mb-2 text-3xl font-semibold">
                    Quel est ton type de peau ?
                </h1>
                <p className="mb-6 text-muted-foreground">
                    Choisis la réponse qui te correspond le mieux pour voir une
                    sélection de produits adaptés.
                </p>

                <div className="flex flex-col gap-3">
                    {skinTypeOptions.map((option) => (
                        <Link
                            key={option.value}
                            href={`/produits?skin_type=${option.value}`}
                            className="rounded-md border p-4 text-center font-medium transition-colors hover:bg-muted"
                        >
                            {option.label}
                        </Link>
                    ))}
                    <Link
                        href="/produits"
                        className="rounded-md border p-4 text-center font-medium text-muted-foreground transition-colors hover:bg-muted"
                    >
                        Tous les types de peaux
                    </Link>
                </div>
            </div>
        </>
    );
}
