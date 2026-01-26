import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, ImageIcon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

interface GalleryImage {
    url: string;
    alt: string;
}

interface ProductImageGalleryProps {
    product: App.Data.ProductData;
}

export function ProductImageGallery({ product }: ProductImageGalleryProps) {
    const allImages = useMemo<GalleryImage[]>(() => {
        const images: GalleryImage[] = [];

        if (product.featuredImageUrl) {
            images.push({ url: product.featuredImageUrl, alt: product.name });
        }

        if (product.images?.length) {
            product.images.forEach((img, i) => {
                images.push({ url: img.url, alt: `${product.name} - Image ${i + 1}` });
            });
        }

        return images;
    }, [product.featuredImageUrl, product.images, product.name]);

    const [selectedIndex, setSelectedIndex] = useState(0);
    const [lightboxOpen, setLightboxOpen] = useState(false);

    const hasImages = allImages.length > 0;
    const hasMultipleImages = allImages.length > 1;

    const goToPrevious = useCallback(() => {
        setSelectedIndex((prev) => (prev === 0 ? allImages.length - 1 : prev - 1));
    }, [allImages.length]);

    const goToNext = useCallback(() => {
        setSelectedIndex((prev) => (prev === allImages.length - 1 ? 0 : prev + 1));
    }, [allImages.length]);

    useEffect(() => {
        if (!hasMultipleImages) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                goToPrevious();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                goToNext();
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [hasMultipleImages, goToPrevious, goToNext]);

    return (
        <div className="flex flex-1 flex-col">
            {hasImages ? (
                <div className="group relative">
                    <button
                        type="button"
                        onClick={() => setLightboxOpen(true)}
                        className="relative cursor-pointer overflow-hidden rounded-lg focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-hidden"
                    >
                        <img
                            alt={allImages[selectedIndex].alt}
                            src={allImages[selectedIndex].url}
                            className="h-full w-full rounded-lg object-cover"
                        />
                    </button>

                    {hasMultipleImages && (
                        <>
                            <Button
                                variant="secondary"
                                size="icon"
                                className="absolute top-1/2 left-2 z-10 -translate-y-1/2 rounded-full opacity-0 transition-opacity group-hover:opacity-100 hover:opacity-100 focus:opacity-100"
                                onClick={goToPrevious}
                            >
                                <ChevronLeft className="h-5 w-5" />
                                <span className="sr-only">Previous image</span>
                            </Button>
                            <Button
                                variant="secondary"
                                size="icon"
                                className="absolute top-1/2 right-2 z-10 -translate-y-1/2 rounded-full opacity-0 transition-opacity group-hover:opacity-100 hover:opacity-100 focus:opacity-100"
                                onClick={goToNext}
                            >
                                <ChevronRight className="h-5 w-5" />
                                <span className="sr-only">Next image</span>
                            </Button>
                        </>
                    )}
                </div>
            ) : (
                <div className="flex h-full w-full items-center justify-center rounded-lg bg-muted py-12">
                    <ImageIcon className="h-24 w-24 text-muted-foreground" />
                </div>
            )}

            {hasMultipleImages && (
                <div className="mt-4">
                    <div className="flex gap-2 overflow-x-auto p-1">
                        {allImages.map((image, index) => (
                            <button
                                key={index}
                                type="button"
                                onClick={() => setSelectedIndex(index)}
                                className={cn(
                                    'relative aspect-square h-16 w-16 shrink-0 overflow-hidden rounded border',
                                    index === selectedIndex
                                        ? 'ring-2 ring-primary ring-offset-2 ring-offset-background'
                                        : 'border-border opacity-70 hover:opacity-100',
                                )}
                            >
                                <img alt={image.alt} src={image.url} className="h-full w-full rounded object-cover" />
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <Dialog open={lightboxOpen} onOpenChange={setLightboxOpen}>
                <DialogContent className="max-w-4xl border-none bg-transparent p-0 shadow-none" showCloseButton={false}>
                    <DialogTitle className="sr-only">{hasImages ? allImages[selectedIndex].alt : product.name}</DialogTitle>
                    <DialogDescription className="sr-only">Product image gallery lightbox</DialogDescription>

                    <div className="relative flex items-center justify-center">
                        {hasMultipleImages && (
                            <Button variant="secondary" size="icon" className="absolute left-2 z-10 rounded-full" onClick={goToPrevious}>
                                <ChevronLeft className="h-5 w-5" />
                                <span className="sr-only">Previous image</span>
                            </Button>
                        )}

                        {hasImages && (
                            <img
                                alt={allImages[selectedIndex].alt}
                                src={allImages[selectedIndex].url}
                                className="max-h-[80vh] w-full rounded-lg object-contain"
                            />
                        )}

                        {hasMultipleImages && (
                            <Button variant="secondary" size="icon" className="absolute right-2 z-10 rounded-full" onClick={goToNext}>
                                <ChevronRight className="h-5 w-5" />
                                <span className="sr-only">Next image</span>
                            </Button>
                        )}
                    </div>

                    {hasMultipleImages && (
                        <div className="mt-2 text-center text-sm text-white">
                            {selectedIndex + 1} / {allImages.length}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}
