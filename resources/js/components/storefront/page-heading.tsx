import type { ReactNode } from 'react';

type PageHeadingProps = {
    title: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
};

export function PageHeading({ title, description, actions }: PageHeadingProps) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0 space-y-2">
                <h1 className="font-heading text-3xl font-semibold tracking-tight md:text-4xl">
                    {title}
                </h1>
                {description ? (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                ) : null}
            </div>
            {actions ? (
                <div className="flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            ) : null}
        </div>
    );
}
