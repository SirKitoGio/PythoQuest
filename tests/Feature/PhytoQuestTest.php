<?php

namespace Tests\Feature;

use App\Models\Plant;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhytoQuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        $this->seed();
        $plant = Plant::first();

        foreach (['/', '/about', '/contact', '/map', '/plants', "/plants/{$plant->id}", '/login', '/register'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_logged_in_pages_render_with_seeded_data(): void
    {
        $this->seed();
        $user = User::where('email', 'demo@phytoquest.test')->first();
        $sighting = $user->sightings()->first();

        $this->actingAs($user);

        foreach (['/', '/dashboard', '/sightings', '/sightings/create', "/sightings/{$sighting->id}", "/sightings/{$sighting->id}/edit", '/identifications', '/identifications/create', '/community', '/history', '/notifications', '/profile'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_reviewer_and_admin_pages_render_with_seeded_data(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'reviewer@phytoquest.test')->first());
        foreach (['/review', '/review?tab=identifications', '/plants/create', '/dashboard'] as $uri) {
            $this->get($uri)->assertOk();
        }

        $this->actingAs(User::where('email', 'keithalan.speirs25@gmail.com')->first());
        foreach (['/admin/users', '/admin/audit-logs'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_researchers_cannot_reach_reviewer_or_admin_pages(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/review', '/plants/create', '/admin/users', '/admin/audit-logs'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        foreach (['/dashboard', '/plants/create', '/sightings', '/sightings/create', '/identifications/create', '/community', '/history', '/review'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_plants_can_be_searched_and_filtered(): void
    {
        Plant::factory()->create(['common_name' => 'Lagundi', 'scientific_name' => 'Vitex negundo', 'category' => 'Medicinal']);
        Plant::factory()->create(['common_name' => 'Banaba', 'scientific_name' => 'Lagerstroemia speciosa', 'category' => 'Trees']);

        $this->get('/plants?q=vitex')->assertSee('Lagundi')->assertDontSee('Banaba');
        $this->get('/plants?category=Trees')->assertSee('Banaba')->assertDontSee('Lagundi');
    }

    public function test_plant_crud(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $data = [
            'common_name' => 'Sambong',
            'scientific_name' => 'Blumea balsamifera',
            'category' => 'Medicinal',
            'habitat' => 'Open grasslands',
            'plant_parts' => 'leaf',
            'physical_features' => 'Aromatic hairy leaves.',
            'source_label' => 'research paper',
            'conservation_status' => 'LC',
        ];

        $this->post('/plants', $data)->assertRedirect();
        $plant = Plant::firstWhere('common_name', 'Sambong');
        $this->assertNotNull($plant);

        $this->put("/plants/{$plant->id}", ['habitat' => 'Riverbanks'] + $data)->assertRedirect("/plants/{$plant->id}");
        $this->assertSame('Riverbanks', $plant->fresh()->habitat);

        $this->delete("/plants/{$plant->id}")->assertRedirect('/plants');
        $this->assertModelMissing($plant);
    }

    public function test_plant_validation_rejects_unknown_category(): void
    {
        $this->actingAs(User::factory()->reviewer()->create());

        $this->post('/plants', ['common_name' => 'X', 'category' => 'Cactus'])
            ->assertSessionHasErrors(['category', 'scientific_name', 'source_label']);
    }

    public function test_sighting_is_created_pending_with_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $plant = Plant::factory()->create();

        $this->actingAs($user)->post('/sightings', [
            'plant_id' => $plant->id,
            'latitude' => 14.5869,
            'longitude' => 121.0360,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'public',
            'photo' => UploadedFile::fake()->image('leaf.jpg'),
        ])->assertRedirect();

        $sighting = $user->sightings()->first();
        $this->assertSame('pending', $sighting->status);
        Storage::disk('public')->assertExists($sighting->photo_path);
    }

    public function test_users_cannot_touch_other_users_sightings(): void
    {
        Plant::factory()->create();
        $owner = User::factory()->create();
        $sighting = Sighting::factory()->create(['user_id' => $owner->id]);

        $this->actingAs(User::factory()->create());

        $this->get("/sightings/{$sighting->id}")->assertForbidden();
        $this->delete("/sightings/{$sighting->id}")->assertForbidden();
        $this->assertModelExists($sighting);
    }

    public function test_passwords_are_hashed_on_registration(): void
    {
        $this->post('/register', [
            'name' => 'Field Student',
            'email' => 'student@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect('/dashboard');

        $user = User::firstWhere('email', 'student@example.com');
        $this->assertNotSame('secret-password', $user->password);
        $this->assertTrue(password_verify('secret-password', $user->password));
    }
}
