<?php

namespace Tests\Feature;

use App\Models\MedicinalAction;
use App\Models\Plant;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_clients_exchange_credentials_for_a_token(): void
    {
        $user = User::factory()->create(['email' => 'field@example.com']);

        $this->postJson(route('api.v1.auth.token.store'), ['email' => 'field@example.com', 'password' => 'wrong', 'device_name' => 'pixel'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $token = $this->postJson(route('api.v1.auth.token.store'), ['email' => 'field@example.com', 'password' => 'password', 'device_name' => 'pixel'])
            ->assertCreated()
            ->assertJsonPath('user.role', 'researcher')
            ->json('token');

        $this->withToken($token)->getJson(route('api.v1.me'))->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson(route('api.v1.sightings.index'))->assertUnauthorized();
        $this->postJson(route('api.v1.identifications.store'))->assertUnauthorized();
    }

    public function test_plant_search_filters_and_profile_detail(): void
    {
        $antibacterial = MedicinalAction::factory()->create(['name' => 'Antibacterial', 'slug' => 'antibacterial']);
        $lagundi = Plant::factory()->create(['common_name' => 'Lagundi', 'scientific_name' => 'Vitex negundo', 'known_toxins' => null]);
        $lagundi->medicinalActions()->attach($antibacterial, ['source_provider' => 'Plants For A Future (PFAF)']);
        Plant::factory()->create(['common_name' => 'Banaba', 'scientific_name' => 'Lagerstroemia speciosa']);

        $this->getJson(route('api.v1.plants.index', ['q' => 'vitex']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.common_name', 'Lagundi');

        $this->getJson(route('api.v1.plants.index', ['action' => 'antibacterial']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.scientific_name', 'Vitex negundo');

        $this->getJson(route('api.v1.plants.show', $lagundi))
            ->assertOk()
            ->assertJsonPath('data.traditional_uses.0.name', 'Antibacterial')
            ->assertJsonPath('data.traditional_uses.0.evidence_level', 'traditional use')
            ->assertJsonPath('data.source.label', $lagundi->source_label)
            ->assertJsonStructure(['data' => ['disclaimer', 'conservation' => ['code', 'is_sensitive']]]);
    }

    public function test_authenticated_searches_are_kept_in_history_but_suggestions_are_not(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.plants.index', ['q' => 'moringa']))->assertOk();
        $this->getJson(route('api.v1.plants.index', ['q' => 'moringa', 'suggest' => 1]))->assertOk();

        $this->assertSame(['moringa'], $user->searchHistories()->pluck('query')->all());
    }

    public function test_sightings_are_submitted_pending_and_scoped_to_their_owner(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $id = $this->postJson(route('api.v1.sightings.store'), [
            'plant_id' => Plant::factory()->create()->id,
            'latitude' => 14.5869,
            'longitude' => 121.036,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'public',
            'photo' => UploadedFile::fake()->image('leaf.jpg'),
        ])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

        $this->getJson(route('api.v1.sightings.index'))->assertJsonCount(1, 'data');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('api.v1.sightings.show', $id))->assertForbidden();
        $this->deleteJson(route('api.v1.sightings.destroy', $id))->assertForbidden();
        $this->getJson(route('api.v1.sightings.index'))->assertJsonCount(0, 'data');
    }

    public function test_only_reviewers_can_use_the_review_endpoints(): void
    {
        $sighting = Sighting::factory()->create([
            'plant_id' => Plant::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
        ]);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('api.v1.review.sightings.index'))->assertForbidden();
        $this->postJson(route('api.v1.review.sightings.store', $sighting), ['decision' => 'trusted'])->assertForbidden();

        Sanctum::actingAs(User::factory()->reviewer()->create());
        $this->getJson(route('api.v1.review.sightings.index'))->assertOk()->assertJsonCount(1, 'data');
        $this->postJson(route('api.v1.review.sightings.store', $sighting), ['decision' => 'trusted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'trusted');
    }

    public function test_identification_is_accepted_asynchronously(): void
    {
        Storage::fake('public');
        config(['queue.default' => 'database']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.identifications.store'), [
            'image' => UploadedFile::fake()->image('flower.jpg'),
            'plant_part' => 'flower',
        ])->assertAccepted()->assertJsonPath('data.status', 'queued');
    }
}
