<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Create (or update the password of) a user from the CLI.
 *   php artisan rustdesk:user admin secret --admin
 *   php artisan rustdesk:user alice secret --email=alice@example.com
 */
class CreateUser extends Command
{
    protected $signature = 'rustdesk:user
        {username : The username}
        {password : The password}
        {--email= : Optional email address}
        {--admin : Grant administrator privileges}';

    protected $description = 'Create a user (or reset its password) for the admin console / client API';

    public function handle(): int
    {
        $username = (string) $this->argument('username');

        $user = User::firstOrNew(['username' => $username]);
        // The User model casts 'password' as 'hashed', so assign the plain value.
        $user->password = (string) $this->argument('password');
        $user->is_admin = (bool) $this->option('admin');
        $user->status = User::STATUS_NORMAL;

        if ($email = $this->option('email')) {
            $user->email = $email;
        }

        $existed = $user->exists;
        $user->save();

        $this->info(sprintf(
            '%s user "%s"%s.',
            $existed ? 'Updated' : 'Created',
            $username,
            $user->is_admin ? ' (admin)' : ''
        ));

        return self::SUCCESS;
    }
}
