<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadedImageService
{
    /** Decode and re-encode raster uploads to remove metadata and appended payloads. */
    public function store(UploadedFile $file): string
    {
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'فایل تصویر قابل پردازش نیست.']);
        }
        ob_start();
        try {
            if (! imagewebp($image, null, 85)) {
                throw new \RuntimeException('Cannot encode image.');
            }$bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        $path = 'products/'.Str::uuid().'.webp';
        if (! Storage::disk('public')->put($path, $bytes)) {
            throw new \RuntimeException('Image storage failed.');
        }

        return $path;
    }
}
