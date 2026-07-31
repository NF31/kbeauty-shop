import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';
import { formatMoney } from '@/lib/money';

type RevenuePoint = {
    date: string;
    revenueCents: number;
};

type RevenueChartProps = {
    data: RevenuePoint[];
};

export function RevenueChart({ data }: RevenueChartProps) {
    return (
        <ResponsiveContainer width="100%" height={260}>
            <AreaChart data={data} margin={{ left: 0, right: 12, top: 12 }}>
                <defs>
                    <linearGradient
                        id="revenue-fill"
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="5%"
                            stopColor="var(--color-chart-1)"
                            stopOpacity={0.35}
                        />
                        <stop
                            offset="95%"
                            stopColor="var(--color-chart-1)"
                            stopOpacity={0}
                        />
                    </linearGradient>
                </defs>
                <CartesianGrid vertical={false} strokeOpacity={0.2} />
                <XAxis
                    dataKey="date"
                    tickFormatter={(value: string) =>
                        new Date(value).toLocaleDateString('fr-FR', {
                            day: '2-digit',
                            month: '2-digit',
                        })
                    }
                    tickLine={false}
                    axisLine={false}
                    fontSize={12}
                    stroke="var(--color-muted-foreground)"
                    minTickGap={24}
                />
                <Tooltip
                    formatter={(value) => formatMoney(Number(value))}
                    labelFormatter={(value) =>
                        new Date(String(value)).toLocaleDateString('fr-FR', {
                            weekday: 'short',
                            day: '2-digit',
                            month: 'short',
                        })
                    }
                    contentStyle={{
                        backgroundColor: 'var(--color-popover)',
                        borderColor: 'var(--color-border)',
                        borderRadius: 8,
                        fontSize: 12,
                    }}
                />
                <Area
                    type="monotone"
                    dataKey="revenueCents"
                    stroke="var(--color-chart-1)"
                    strokeWidth={2}
                    fill="url(#revenue-fill)"
                />
            </AreaChart>
        </ResponsiveContainer>
    );
}
