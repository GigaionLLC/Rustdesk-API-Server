<?php

namespace Database\Seeders;

use App\Models\DeviceGroup;
use App\Models\Group;
use App\Models\Strategy;
use Illuminate\Database\Seeder;

/**
 * Seeds a small set of sample records (a default strategy, a couple of user groups and
 * device groups) useful for a fresh demo install.
 *
 * Idempotent: records are matched by name and updated in place, so re-running this seeder
 * is safe and will not create duplicates.
 */
class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Strategy::updateOrCreate(
            ['name' => 'Default Policy'],
            [
                'enabled' => true,
                'options' => [
                    'enable-keyboard' => 'Y',
                    'enable-clipboard' => 'Y',
                    'enable-file-transfer' => 'Y',
                    'enable-audio' => 'Y',
                    'enable-remote-restart' => 'N',
                ],
                'extra' => [],
                'modified_at' => time(),
                'note' => 'Default policy seeded for demo installs.',
            ],
        );

        $groups = [
            ['name' => 'Default Group', 'type' => Group::TYPE_DEFAULT, 'note' => 'Default user group.'],
            ['name' => 'Shared Group', 'type' => Group::TYPE_SHARED, 'note' => 'Shared user group.'],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate(
                ['name' => $group['name']],
                ['type' => $group['type'], 'note' => $group['note']],
            );
        }

        $deviceGroups = [
            ['name' => 'Workstations', 'note' => 'Office workstations.'],
            ['name' => 'Servers', 'note' => 'Managed servers.'],
        ];

        foreach ($deviceGroups as $deviceGroup) {
            DeviceGroup::updateOrCreate(
                ['name' => $deviceGroup['name']],
                ['note' => $deviceGroup['note']],
            );
        }
    }
}
