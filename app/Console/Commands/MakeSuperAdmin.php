<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'events:make-super-admin {email : The user email to promote}';

    protected $description = 'Set a user\'s role to super_admin and clear company (tenant) association.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $user->forceFill([
            'role'       => 'super_admin',
            'company_id' => null,
        ])->save();

        $this->info("User [{$email}] is now a super admin.");

        return self::SUCCESS;
    }
}
