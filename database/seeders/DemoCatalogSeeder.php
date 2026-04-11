<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Platform Owner',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Admin Curator',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $editor = User::factory()->editor()->create([
            'name' => 'Catalog Editor',
            'username' => 'editor',
            'email' => 'editor@example.com',
        ]);

        $moderator = User::factory()->moderator()->create([
            'name' => 'Review Moderator',
            'username' => 'moderator',
            'email' => 'moderator@example.com',
        ]);

        $member = User::factory()->create([
            'name' => 'Member Viewer',
            'username' => 'member',
            'email' => 'member@example.com',
        ]);

        $contributors = User::factory()->count(2)->contributor()->create()
            ->prepend(User::factory()->contributor()->create([
                'name' => 'Lead Contributor',
                'username' => 'contributor',
                'email' => 'contributor@example.com',
            ]))
            ->values();
    }
}
