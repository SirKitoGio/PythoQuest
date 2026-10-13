<?php

namespace Tests\Feature;

use App\Jobs\RunIdentification;
use App\Models\Identification;
use App\Models\Plant;
use App\Models\User;
use App\Notifications\IdentificationEscalatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::preventStrayRequests();
    }

    private function fakeModel(array $candidates): void
    {
        Http::fake(['ml.test/v1/identify' => Http::response([
            'model' => 'bioclip',
            'model_version' => 'hf-hub:imageomics/bioclip@test',
            'plant_part' => 'leaf',
            'candidates' => $candidates,
            'latency_ms' => 42,
        ])]);
    }

    public function test_scan_returns_ranked_candidates_linked_to_catalogue_profiles(): void
    {
        $guava = Plant::factory()->create(['common_name' => 'Bayabas', 'scientific_name' => 'Psidium guajava']);
        $this->fakeModel([
            ['scientific_name' => 'Psidium guajava', 'family' => 'Myrtaceae', 'score' => 0.91],
            ['scientific_name' => 'Unknown species', 'family' => null, 'score' => 0.04],
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('identifications.store'), [
            'image' => UploadedFile::fake()->image('leaf.jpg'),
            'plant_part' => 'leaf',
        ])->assertRedirect();

        $identification = $user->identifications()->sole();
        $this->assertSame('completed', $identification->status);
        $this->assertSame($guava->id, $identification->top_plant_id);
        $this->assertSame(0.91, $identification->top_confidence);
        $this->assertFalse($identification->needs_more_images);
        $this->assertSame([$guava->id, null], array_column($identification->candidates, 'plant_id'));
        Http::assertSent(fn ($request) => $request->hasFile('image')
            && collect($request->data())->contains(fn (array $part) => $part['name'] === 'plant_part' && $part['contents'] === 'leaf'));

        $this->get(route('identifications.show', $identification))->assertOk()->assertSee('Bayabas')->assertSee('91.0%');
    }

    public function test_the_service_token_is_sent_when_configured(): void
    {
        config(['services.ml.token' => 'shared-secret']);
        $this->fakeModel([]);

        $this->actingAs(User::factory()->create())->post(route('identifications.store'), [
            'image' => UploadedFile::fake()->image('leaf.jpg'),
            'plant_part' => 'leaf',
        ]);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer shared-secret'));
    }

    public function test_low_confidence_results_ask_for_more_images(): void
    {
        Plant::factory()->create(['scientific_name' => 'Psidium guajava']);
        $this->fakeModel([['scientific_name' => 'Psidium guajava', 'score' => 0.2]]);

        $this->actingAs(User::factory()->create())->post(route('identifications.store'), [
            'image' => UploadedFile::fake()->image('blurry.jpg'),
            'plant_part' => 'bark',
        ]);

        $this->assertTrue(Identification::sole()->needs_more_images);
    }

    public function test_service_failure_marks_the_scan_failed(): void
    {
        Http::fake(['ml.test/*' => Http::response(['detail' => 'model not loaded'], 503)]);
        $identification = Identification::factory()->create();
        Storage::disk('public')->put($identification->image_path, 'jpeg-bytes');
        $job = new RunIdentification($identification);

        try {
            $job->handle();
            $this->fail('Expected the ML request to throw.');
        } catch (RequestException) {
            $job->failed(null);
        }

        $this->assertSame('failed', $identification->fresh()->status);
        $this->assertNotNull($identification->fresh()->error);
    }

    public function test_scans_are_private_to_their_owner_and_reviewers(): void
    {
        $identification = Identification::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('identifications.show', $identification))->assertForbidden();
        $this->actingAs(User::factory()->reviewer()->create())->get(route('identifications.show', $identification))->assertOk();
    }

    public function test_escalated_scan_reaches_reviewers_who_can_confirm_the_species(): void
    {
        Notification::fake();
        $plant = Plant::factory()->create();
        $reviewer = User::factory()->reviewer()->create();
        $identification = Identification::factory()->completed($plant, 0.2)->create();

        $this->actingAs($identification->user)->post(route('identifications.escalation.store', $identification))->assertRedirect();
        $this->actingAs($identification->user)->post(route('identifications.escalation.store', $identification))->assertForbidden();
        Notification::assertSentTo($reviewer, IdentificationEscalatedNotification::class);

        $this->actingAs($reviewer)->post(route('review.identifications.store', $identification), ['plant_id' => $plant->id])->assertRedirect();

        $this->assertSame($plant->id, $identification->fresh()->confirmed_plant_id);
        $this->assertSame($reviewer->id, $identification->fresh()->reviewed_by);
    }

    public function test_sighting_can_be_logged_from_a_scan_reusing_its_photo(): void
    {
        $plant = Plant::factory()->create();
        $identification = Identification::factory()->completed($plant)->create(['image_path' => 'identifications/scan.jpg']);
        Storage::disk('public')->put('identifications/scan.jpg', 'jpeg-bytes');

        $this->actingAs($identification->user)->post(route('sightings.store'), [
            'plant_id' => $plant->id,
            'identification_id' => $identification->id,
            'latitude' => 14.5869,
            'longitude' => 121.036,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'public',
        ])->assertRedirect();

        $sighting = $identification->sightings()->sole();
        Storage::disk('public')->assertExists($sighting->photo_path);
    }

    public function test_users_cannot_attach_someone_elses_scan(): void
    {
        $identification = Identification::factory()->completed(Plant::factory()->create())->create();

        $this->actingAs(User::factory()->create())->post(route('sightings.store'), [
            'plant_id' => $identification->top_plant_id,
            'identification_id' => $identification->id,
            'latitude' => 14.5869,
            'longitude' => 121.036,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'public',
        ])->assertSessionHasErrors('identification_id');
    }
}
