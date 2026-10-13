<?php

namespace App\Actions;

use App\Events\SightingSubmitted;
use App\Models\Identification;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Creates or resubmits a sighting. Every submission enters the review queue as pending (FR-10).
 */
class SubmitSighting
{
    public function __construct(private StoreSanitizedImage $storeImage) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?UploadedFile $photo = null): Sighting
    {
        if ($photo) {
            $data['photo_path'] = $this->storeImage->handle($photo, 'sightings');
        } elseif ($identificationId = $data['identification_id'] ?? null) {
            $data['photo_path'] = $this->copyIdentificationImage($user->identifications()->findOrFail($identificationId));
        }

        $sighting = DB::transaction(function () use ($user, $data) {
            $sighting = $user->sightings()->create(['status' => 'pending'] + $data);

            SightingSubmitted::dispatch($sighting);

            return $sighting;
        });

        return $sighting;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sighting $sighting, array $data, ?UploadedFile $photo = null): Sighting
    {
        if ($photo) {
            $previous = $sighting->photo_path;
            $data['photo_path'] = $this->storeImage->handle($photo, 'sightings');
        }

        DB::transaction(function () use ($sighting, $data) {
            $sighting->update(['status' => 'pending'] + $data);

            SightingSubmitted::dispatch($sighting, isResubmission: true);
        });

        if (isset($previous)) {
            Storage::disk(config('filesystems.media'))->delete($previous);
        }

        return $sighting;
    }

    private function copyIdentificationImage(Identification $identification): string
    {
        $path = 'sightings/'.basename($identification->image_path);
        Storage::disk(config('filesystems.media'))->copy($identification->image_path, $path);

        return $path;
    }
}
