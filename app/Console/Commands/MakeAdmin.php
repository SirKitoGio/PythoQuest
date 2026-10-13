<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Promotes a registered account to admin. Production never runs the demo seeders,
 * so the first administrator registers normally and is promoted from the server.
 */
#[Signature('phytoquest:make-admin {email : Email of an already registered user}')]
#[Description('Promote a registered user to administrator')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if (! $user) {
            $this->error('No registered user has that email. Register on the site first.');

            return self::FAILURE;
        }

        $previous = $user->role;
        $user->forceFill(['role' => UserRole::Admin])->save();
        AuditLog::record('user.role_changed', $user, ['role' => [$previous->value, UserRole::Admin->value], 'via' => 'console']);

        $this->info("{$user->name} is now an administrator.");

        return self::SUCCESS;
    }
}
