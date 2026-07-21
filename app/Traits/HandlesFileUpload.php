<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandlesFileUpload
{
    /**
     * Upload an image to storage.
     *
     * @return string|false
     */
    protected function uploadImage(UploadedFile $file, string $directory, ?string $disk = null)
    {
        $disk = $disk ?? config('filesystems.default');

        return $file->store($directory, $disk);
    }

    /**
     * Delete an image from storage if it exists.
     */
    protected function deleteImage(?string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? config('filesystems.default');

        if ($path && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Replace an old image with a new one.
     *
     * @return string|false
     */
    protected function replaceImage(UploadedFile $file, ?string $oldPath, string $directory, ?string $disk = null)
    {
        $disk = $disk ?? config('filesystems.default');

        $this->deleteImage($oldPath, $disk);

        return $this->uploadImage($file, $directory, $disk);
    }
}
