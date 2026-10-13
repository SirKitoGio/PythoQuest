<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_promote_a_researcher_and_the_change_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $researcher = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $researcher), ['role' => 'reviewer'])
            ->assertRedirect();

        $this->assertSame(UserRole::Reviewer, $researcher->fresh()->role);
        $this->assertDatabaseHas(AuditLog::class, ['action' => 'user.role_changed', 'auditable_id' => $researcher->id, 'user_id' => $admin->id]);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), ['role' => 'researcher'])
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_reviewers_cannot_change_roles(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->reviewer()->create())
            ->patch(route('admin.users.update', $target), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertSame(UserRole::Researcher, $target->fresh()->role);
    }

    public function test_new_registrations_are_researchers(): void
    {
        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $this->assertSame(UserRole::Researcher, User::firstWhere('email', 'new@example.com')->role);
    }

    public function test_console_can_promote_the_first_administrator(): void
    {
        $user = User::factory()->create(['email' => 'first@example.com']);

        $this->artisan('phytoquest:make-admin', ['email' => 'first@example.com'])->assertSuccessful();
        $this->artisan('phytoquest:make-admin', ['email' => 'nobody@example.com'])->assertFailed();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }
}
