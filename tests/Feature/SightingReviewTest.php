<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Plant;
use App\Models\Sighting;
use App\Models\User;
use App\Notifications\NewSubmissionForReviewNotification;
use App\Notifications\SightingReviewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SightingReviewTest extends TestCase
{
    use RefreshDatabase;

    private function pendingSighting(array $attributes = []): Sighting
    {
        return Sighting::factory()->create($attributes + ['plant_id' => Plant::factory(), 'user_id' => User::factory(), 'status' => 'pending', 'visibility' => 'public']);
    }

    public function test_reviewer_decision_updates_status_notifies_submitter_and_is_audited(): void
    {
        Notification::fake();
        $reviewer = User::factory()->reviewer()->create();
        $sighting = $this->pendingSighting();

        $this->actingAs($reviewer)
            ->post(route('review.sightings.store', $sighting), ['decision' => 'needs correction', 'note' => 'Photograph the leaf underside.'])
            ->assertRedirect();

        $sighting->refresh();
        $this->assertSame('needs correction', $sighting->status);
        $this->assertSame($reviewer->id, $sighting->reviewed_by);
        $this->assertSame('Photograph the leaf underside.', $sighting->review_note);
        Notification::assertSentTo($sighting->user, SightingReviewedNotification::class);
        $this->assertDatabaseHas(AuditLog::class, ['action' => 'sighting.reviewed', 'auditable_id' => $sighting->id, 'user_id' => $reviewer->id]);
    }

    public function test_rejection_requires_a_note(): void
    {
        $sighting = $this->pendingSighting();

        $this->actingAs(User::factory()->reviewer()->create())
            ->post(route('review.sightings.store', $sighting), ['decision' => 'rejected'])
            ->assertSessionHasErrors('note');

        $this->assertSame('pending', $sighting->fresh()->status);
    }

    public function test_researchers_and_submitters_cannot_review(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $ownSighting = $this->pendingSighting(['user_id' => $reviewer->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('review.sightings.store', $ownSighting), ['decision' => 'trusted'])
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->post(route('review.sightings.store', $ownSighting), ['decision' => 'trusted'])
            ->assertForbidden();

        $this->assertSame('pending', $ownSighting->fresh()->status);
    }

    public function test_community_confirmations_add_evidence_without_changing_status(): void
    {
        $sighting = $this->pendingSighting();
        $member = User::factory()->create();

        $this->actingAs($member)->post(route('sightings.confirmation.store', $sighting))->assertRedirect();
        $this->actingAs($member)->post(route('sightings.confirmation.store', $sighting))->assertRedirect();

        $this->assertSame(1, $sighting->confirmations()->count());
        $this->assertSame('pending', $sighting->fresh()->status);
    }

    public function test_expert_rejection_overrides_earlier_community_confirmations(): void
    {
        $sighting = $this->pendingSighting();
        $this->actingAs(User::factory()->create())->post(route('sightings.confirmation.store', $sighting));

        $this->actingAs(User::factory()->reviewer()->create())
            ->post(route('review.sightings.store', $sighting), ['decision' => 'rejected', 'note' => 'Misidentified: this is Vitex trifolia.']);

        $this->assertSame('rejected', $sighting->fresh()->status);
        $this->assertSame(1, $sighting->confirmations()->count());
    }

    public function test_owners_cannot_confirm_their_own_or_private_records(): void
    {
        $sighting = $this->pendingSighting();
        $private = $this->pendingSighting(['visibility' => 'private']);

        $this->actingAs($sighting->user)->post(route('sightings.confirmation.store', $sighting))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('sightings.confirmation.store', $private))->assertForbidden();
    }

    public function test_correcting_a_sighting_resubmits_it_for_review(): void
    {
        Notification::fake();
        Storage::fake('public');
        $reviewer = User::factory()->reviewer()->create();
        $sighting = $this->pendingSighting(['status' => 'needs correction']);

        $this->actingAs($sighting->user)->put(route('sightings.update', $sighting), [
            'plant_id' => $sighting->plant_id,
            'latitude' => 14.5869,
            'longitude' => 121.036,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'public',
            'photo' => UploadedFile::fake()->image('retake.jpg'),
        ])->assertRedirect(route('sightings.show', $sighting));

        $this->assertSame('pending', $sighting->fresh()->status);
        Notification::assertSentTo($reviewer, NewSubmissionForReviewNotification::class, fn ($notification) => $notification->isResubmission);
    }

    public function test_new_sightings_require_photo_evidence(): void
    {
        $this->actingAs(User::factory()->create())->post(route('sightings.store'), [
            'plant_id' => Plant::factory()->create()->id,
            'latitude' => 14.5869,
            'longitude' => 121.036,
            'observed_on' => now()->toDateString(),
            'plant_part' => 'leaf',
            'visibility' => 'public',
        ])->assertSessionHasErrors('photo');
    }
}
