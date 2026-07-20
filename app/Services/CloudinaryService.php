<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    public function __construct(private readonly Cloudinary $cloudinary) {}

    /**
     * Upload a file to Cloudinary and return its `public_id` — the only
     * value stored in `product_images.path` (docs/FEATURES.md 5.1).
     */
    public function upload(UploadedFile $file, string $folder = 'products'): string
    {
        $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
        ]);

        return $result['public_id'];
    }

    public function destroy(string $publicId): void
    {
        $this->cloudinary->uploadApi()->destroy($publicId);
    }

    /**
     * Delivery URL built at request time from a `public_id` — transformed
     * on the fly by Cloudinary rather than duplicating files in storage.
     */
    public function url(string $publicId, int $width = 800, int $height = 800): string
    {
        $cloudName = $this->cloudinary->configuration->cloud->cloudName;

        return "https://res.cloudinary.com/{$cloudName}/image/upload/w_{$width},h_{$height},c_fill,q_auto,f_auto/{$publicId}";
    }
}
