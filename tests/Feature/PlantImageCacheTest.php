<?php

namespace Tests\Feature;

use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlantImageCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_images_are_stored_locally_and_served_from_storage(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'pfaf.org/Admin/PlantImages/Good.gif' => Http::response(UploadedFile::fake()->image('good.gif', 1200, 600)->getContent()),
            'pfaf.org/Admin/PlantImages/Missing.jpg' => Http::response('', 404),
        ]);
        $cached = Plant::factory()->create(['image_url' => 'https://pfaf.org/Admin/PlantImages/Good.gif']);
        $missing = Plant::factory()->create(['image_url' => 'https://pfaf.org/Admin/PlantImages/Missing.jpg']);

        $this->artisan('phytoquest:cache-plant-images')->assertSuccessful();

        $cached->refresh();
        $this->assertSame("plants/{$cached->id}.jpg", $cached->image_path);
        [$width] = getimagesizefromstring(Storage::disk('public')->get($cached->image_path));
        $this->assertSame(900, $width);
        $this->assertSame(Storage::disk('public')->url($cached->image_path), $cached->photoUrl());

        $this->assertNull($missing->fresh()->image_path);
        $this->assertSame('https://pfaf.org/Admin/PlantImages/Missing.jpg', $missing->fresh()->photoUrl());
    }

    public function test_already_cached_images_are_skipped(): void
    {
        Http::preventStrayRequests();
        Plant::factory()->create(['image_url' => 'https://pfaf.org/Admin/PlantImages/A.jpg', 'image_path' => null])
            ->forceFill(['image_path' => 'plants/1.jpg'])->save();

        $this->artisan('phytoquest:cache-plant-images')
            ->expectsOutput('All plant images are already cached.')
            ->assertSuccessful();
    }
}
