import { useSidebar } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';

export function AnimatedMenuButton({ className }: { className?: string }) {
    const { toggleSidebar, isMobile, state, openMobile } = useSidebar();
    const isOpen = isMobile ? openMobile : state === 'expanded';

    return (
        <button
            type="button"
            onClick={toggleSidebar}
            aria-label="Basculer la navigation"
            className={cn(
                'flex size-7 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-accent',
                className,
            )}
        >
            <span className="relative flex h-3.5 w-4 flex-col justify-between">
                <span
                    className={cn(
                        'h-0.5 w-full origin-center rounded-full bg-foreground transition-transform duration-300 ease-in-out',
                        isOpen && 'translate-y-[6px] rotate-45',
                    )}
                />
                <span
                    className={cn(
                        'h-0.5 w-full rounded-full bg-foreground transition-opacity duration-200 ease-in-out',
                        isOpen && 'opacity-0',
                    )}
                />
                <span
                    className={cn(
                        'h-0.5 w-full origin-center rounded-full bg-foreground transition-transform duration-300 ease-in-out',
                        isOpen && '-translate-y-[6px] -rotate-45',
                    )}
                />
            </span>
        </button>
    );
}
