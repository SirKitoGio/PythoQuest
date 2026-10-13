<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Re-encodes uploaded photos before storing them. Re-encoding drops EXIF metadata, so a
 * photo cannot leak the exact GPS position of a protected or private sighting, and it
 * discards anything in the file that is not image data.
 */
class StoreSanitizedImage
{
    public const MAX_DIMENSION = 2048;

    public function handle(UploadedFile $file, string $directory): string
    {
        $path = $directory.'/'.Str::uuid().'.jpg';

        return $this->storeContents((string) file_get_contents($file->getRealPath()), $path);
    }

    /**
     * Re-encode raw image bytes (JPEG, PNG, GIF, WebP) as a JPEG at the given path.
     */
    public function storeContents(string $bytes, string $path, int $maxDimension = self::MAX_DIMENSION): string
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            throw new InvalidArgumentException('The file is not a readable image.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));

        if ($scale < 1) {
            $image = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
        }

        // Flatten transparency (GIF/PNG) onto white so it doesn't turn black in JPEG.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 85);
        $contents = (string) ob_get_clean();
        imagedestroy($image);
        imagedestroy($canvas);

        Storage::disk(config('filesystems.media'))->put($path, $contents);

        return $path;
    }
}
