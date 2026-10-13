<?php

namespace App\Actions;

use App\Jobs\RunIdentification;
use App\Models\Identification;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Stores a scan and queues it for the ML service so a slow model never blocks the request.
 */
class StartIdentification
{
    public function __construct(private StoreSanitizedImage $storeImage) {}

    public function handle(User $user, UploadedFile $image, string $plantPart): Identification
    {
        $identification = $user->identifications()->create([
            'image_path' => $this->storeImage->handle($image, 'identifications'),
            'plant_part' => $plantPart,
            'status' => 'queued',
        ]);

        RunIdentification::dispatch($identification)->afterCommit();

        return $identification;
    }
}
