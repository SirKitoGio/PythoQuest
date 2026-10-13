<?php

namespace Tests\Feature;

use App\Models\Plant;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 14.58693;

    private const LNG = 121.03612;

    private function sighting(string $status, string $visibility, string $conservation = 'LC', ?User $owner = null): Sighting
    {
        return Sighting::factory()->create([
            'plant_id' => Plant::factory()->create(['conservation_status' => $conservation]),
            'user_id' => $owner ?? User::factory(),
            'status' => $status,
            'visibility' => $visibility,
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'location_name' => 'JRU Botanical Corner',
        ]);
    }

    /**
     * @return array<int, array{precision: string, coordinates: array<int, float>, location_name: ?string}>
     */
    private function mapFeatures(): array
    {
        return collect($this->getJson(route('api.v1.map.sightings'))->assertOk()->json('features'))
            ->map(fn (array $feature) => [
                'precision' => $feature['properties']['precision'],
                'coordinates' => $feature['geometry']['coordinates'],
                'location_name' => $feature['properties']['location_name'],
            ])->all();
    }

    public function test_trusted_public_records_of_common_plants_show_exact_pins(): void
    {
        $this->sighting('trusted', 'public');

        $this->assertSame([[
            'precision' => 'exact',
            'coordinates' => [self::LNG, self::LAT],
            'location_name' => 'JRU Botanical Corner',
        ]], $this->mapFeatures());
    }

    public function test_protected_unverified_and_sensitive_records_are_generalized(): void
    {
        $this->sighting('trusted', 'protected');
        $this->sighting('pending', 'public');
        $this->sighting('trusted', 'public', conservation: 'EN');
        $this->sighting('sensitive-restricted', 'public');

        $features = $this->mapFeatures();

        $this->assertCount(4, $features);
        foreach ($features as $feature) {
            $this->assertSame('generalized', $feature['precision']);
            $this->assertSame([121.035, 14.585], $feature['coordinates']);
            $this->assertNull($feature['location_name']);
        }
    }

    public function test_private_and_rejected_records_never_reach_the_public_map(): void
    {
        $this->sighting('trusted', 'private');
        $this->sighting('rejected', 'public');

        $this->assertSame([], $this->mapFeatures());
    }

    public function test_owners_and_reviewers_see_exact_coordinates_of_protected_records(): void
    {
        $owner = User::factory()->create();
        $this->sighting('pending', 'protected', conservation: 'CR', owner: $owner);

        Sanctum::actingAs($owner);
        $this->assertSame('exact', $this->mapFeatures()[0]['precision']);

        Sanctum::actingAs(User::factory()->reviewer()->create());
        $this->assertSame('exact', $this->mapFeatures()[0]['precision']);

        Sanctum::actingAs(User::factory()->create());
        $this->assertSame('generalized', $this->mapFeatures()[0]['precision']);
    }

    public function test_uploaded_photos_are_stripped_of_exif_location_metadata(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $photo = UploadedFile::fake()->image('leaf.jpg', 64, 64);
        $jpeg = file_get_contents($photo->getRealPath());
        $exif = "Exif\0\0GPSLatitude=14.58693";
        file_put_contents($photo->getRealPath(), substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));

        $this->actingAs($user)->post(route('sightings.store'), [
            'plant_id' => Plant::factory()->create()->id,
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'protected',
            'photo' => $photo,
        ])->assertRedirect();

        $stored = Storage::disk('public')->get($user->sightings()->first()->photo_path);
        $this->assertStringStartsWith("\xFF\xD8", $stored);
        $this->assertStringNotContainsString('GPSLatitude', $stored);
    }
}
