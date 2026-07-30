import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type StatCardProps = {
    label: string;
    value: string | number;
    icon: LucideIcon;
    hint?: string;
    tone?: 'default' | 'warning';
};

export function StatCard({
    label,
    value,
    icon: Icon,
    hint,
    tone = 'default',
}: StatCardProps) {
    return (
        <Card>
            <CardContent className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="mt-1 text-3xl font-semibold tracking-tight">
                        {value}
                    </p>
                    {hint ? (
                        <p className="mt-1 text-xs text-muted-foreground">
                            {hint}
                        </p>
                    ) : null}
                </div>
                <div
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted',
                        tone === 'warning' &&
                            'bg-amber-100 dark:bg-amber-900/40',
                    )}
                >
                    <Icon
                        className={cn(
                            'size-5 text-muted-foreground',
                            tone === 'warning' &&
                                'text-amber-600 dark:text-amber-400',
                        )}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
