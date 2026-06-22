<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default administrator. Created only if absent, so a password later changed in the
        // UI is never overwritten by re-seeding. Use `php artisan rustdesk:user ... --admin`
        // to add more admins or reset a password.
        $adminUsername = env('ADMIN_USER', 'admin');
        if (! User::where('username', $adminUsername)->exists()) {
            User::create([
                'username' => $adminUsername,
                'password' => env('ADMIN_PASS', 'admin123456'),
                'is_admin' => true,
                'status' => User::STATUS_NORMAL,
                'display_name' => 'Administrator',
            ]);
        }

        $this->call([
            MailTemplateSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
