import { Minus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';

type QuantitySelectorProps = {
    value: number;
    onChange: (value: number) => void;
    max: number;
    min?: number;
};

export function QuantitySelector({
    value,
    onChange,
    max,
    min = 1,
}: QuantitySelectorProps) {
    const clamp = (next: number) => Math.min(max, Math.max(min, next));

    return (
        <div className="inline-flex items-center rounded-md border">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="rounded-r-none"
                disabled={value <= min}
                onClick={() => onChange(clamp(value - 1))}
                aria-label="Diminuer la quantité"
            >
                <Minus className="size-4" />
            </Button>
            <input
                type="text"
                inputMode="numeric"
                value={value}
                onChange={(e) => {
                    const parsed = Number(e.target.value.replace(/\D/g, ''));

                    if (!Number.isNaN(parsed) && parsed > 0) {
                        onChange(clamp(parsed));
                    }
                }}
                onBlur={(e) => {
                    if (e.target.value === '' || Number(e.target.value) < min) {
                        onChange(min);
                    }
                }}
                className="w-10 border-x bg-transparent text-center text-sm tabular-nums outline-none"
                aria-label="Quantité"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="rounded-l-none"
                disabled={value >= max}
                onClick={() => onChange(clamp(value + 1))}
                aria-label="Augmenter la quantité"
            >
                <Plus className="size-4" />
            </Button>
        </div>
    );
}
