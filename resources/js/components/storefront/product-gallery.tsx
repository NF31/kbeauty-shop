import { XIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { CarouselApi } from '@/components/ui/carousel';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

export type ProductGalleryImage = {
    id: number;
    url: string;
    alt_text: string | null;
    product_variant_id: number | null;
};

type ProductGalleryProps = {
    images: ProductGalleryImage[];
    productName: string;
    /**
     * When set, only generic images (product_variant_id null) plus images
     * belonging to this variant are shown — falls back to all images when
     * that combination is empty (docs/FEATURES.md 5.2).
     */
    selectedVariantId?: number | null;
};

function useCarouselIndex() {
    const [api, setApi] = useState<CarouselApi>();
    const [index, setIndex] = useState(0);

    useEffect(() => {
        if (!api) {
            return;
        }

        const onSelect = () => setIndex(api.selectedScrollSnap());
        onSelect();
        api.on('select', onSelect);
        api.on('reInit', onSelect);

        return () => {
            api.off('select', onSelect);
            api.off('reInit', onSelect);
        };
    }, [api]);

    return { api, setApi, index };
}

export function ProductGallery({
    images,
    productName,
    selectedVariantId = null,
}: ProductGalleryProps) {
    const { api, setApi, index: selectedIndex } = useCarouselIndex();
    const {
        api: lightboxApi,
        setApi: setLightboxApi,
        index: lightboxIndex,
    } = useCarouselIndex();
    const [lightboxOpen, setLightboxOpen] = useState(false);

    const filtered =
        selectedVariantId === null
            ? images
            : images.filter(
                  (image) =>
                      image.product_variant_id === null ||
                      image.product_variant_id === selectedVariantId,
              );

    const displayedImages = filtered.length > 0 ? filtered : images;

    if (displayedImages.length === 0) {
        return (
            <div
                role="img"
                aria-label={`Aucune image pour ${productName}`}
                className="aspect-square w-full animate-pulse rounded-lg bg-muted"
            />
        );
    }

    const activeImage = displayedImages[lightboxIndex] ?? displayedImages[0];

    return (
        <div className="flex flex-col gap-3">
            <Carousel setApi={setApi} className="w-full">
                <CarouselContent>
                    {displayedImages.map((image) => (
                        <CarouselItem key={image.id}>
                            <button
                                type="button"
                                onClick={() => setLightboxOpen(true)}
                                className="group block w-full cursor-zoom-in overflow-hidden rounded-lg"
                            >
                                <img
                                    src={image.url}
                                    alt={image.alt_text ?? productName}
                                    className="aspect-square w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                                />
                            </button>
                        </CarouselItem>
                    ))}
                </CarouselContent>
                {displayedImages.length > 1 && (
                    <>
                        <CarouselPrevious className="transition-opacity" />
                        <CarouselNext className="transition-opacity" />
                    </>
                )}
            </Carousel>

            {displayedImages.length > 1 && (
                <div className="flex gap-2 overflow-x-auto">
                    {displayedImages.map((image, index) => (
                        <button
                            key={image.id}
                            type="button"
                            onClick={() => api?.scrollTo(index)}
                            aria-label={`Voir l'image ${index + 1}`}
                            aria-current={index === selectedIndex}
                            className={cn(
                                'size-16 shrink-0 overflow-hidden rounded-md border-2 opacity-60 transition-all duration-200',
                                index === selectedIndex &&
                                    'border-primary opacity-100',
                            )}
                        >
                            <img
                                src={image.url}
                                alt=""
                                className="size-full object-cover"
                            />
                        </button>
                    ))}
                </div>
            )}

            <Dialog open={lightboxOpen} onOpenChange={setLightboxOpen}>
                <DialogContent
                    showCloseButton={false}
                    overlayClassName="bg-black/95 backdrop-blur-md duration-300"
                    className="flex h-[95vh] w-[95vw] max-w-6xl flex-col gap-4 border-none bg-transparent p-4 shadow-none duration-300 sm:max-w-6xl"
                >
                    <DialogTitle className="sr-only">
                        {activeImage.alt_text ?? productName}
                    </DialogTitle>

                    <DialogClose asChild>
                        <button
                            type="button"
                            aria-label="Fermer"
                            className="absolute top-4 right-4 z-20 flex size-11 items-center justify-center rounded-full border border-white/10 bg-white/10 text-white shadow-lg backdrop-blur-md transition-all hover:scale-105 hover:bg-white/20"
                        >
                            <XIcon className="size-5" />
                        </button>
                    </DialogClose>

                    {displayedImages.length > 1 && (
                        <div className="absolute top-4 left-4 z-20 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-sm font-medium text-white shadow-lg backdrop-blur-md">
                            {lightboxIndex + 1} / {displayedImages.length}
                        </div>
                    )}

                    <Carousel
                        setApi={setLightboxApi}
                        opts={{ startIndex: selectedIndex }}
                        className="min-h-0 flex-1"
                    >
                        <CarouselContent className="h-full">
                            {displayedImages.map((image) => (
                                <CarouselItem
                                    key={image.id}
                                    className="flex h-full items-center justify-center"
                                >
                                    <img
                                        src={image.url}
                                        alt={image.alt_text ?? productName}
                                        className="max-h-full max-w-full animate-in rounded-lg object-contain duration-500 ease-out zoom-in-95 fade-in"
                                    />
                                </CarouselItem>
                            ))}
                        </CarouselContent>
                        {displayedImages.length > 1 && (
                            <>
                                <CarouselPrevious className="left-4 size-11 border-white/10 bg-white/10 text-white backdrop-blur-md transition-all hover:scale-105 hover:bg-white/20" />
                                <CarouselNext className="right-4 size-11 border-white/10 bg-white/10 text-white backdrop-blur-md transition-all hover:scale-105 hover:bg-white/20" />
                            </>
                        )}
                    </Carousel>

                    {displayedImages.length > 1 && (
                        <div className="flex shrink-0 justify-center gap-2 overflow-x-auto">
                            {displayedImages.map((image, index) => (
                                <button
                                    key={image.id}
                                    type="button"
                                    onClick={() => lightboxApi?.scrollTo(index)}
                                    aria-label={`Voir l'image ${index + 1}`}
                                    aria-current={index === lightboxIndex}
                                    className={cn(
                                        'size-14 shrink-0 overflow-hidden rounded-md border-2 opacity-50 transition-all duration-200 hover:opacity-100',
                                        index === lightboxIndex &&
                                            'border-white opacity-100',
                                    )}
                                >
                                    <img
                                        src={image.url}
                                        alt=""
                                        className="size-full object-cover"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}
