<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin agency
        $agency = Agency::firstOrCreate(
            ['email' => 'admin@levora.uz'],
            [
                'name' => 'Levora HQ',
                'legal_name' => 'Levora LLC',
                'legal_address' => 'Tashkent, Uzbekistan',
                'phone' => '+998712334455',
                'director' => 'Admin',
                'is_active' => true,
            ]
        );

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@levora.uz'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'agency_id' => $agency->id,
                'phone' => '+998712334455',
                'is_active' => true,
            ]
        );
        $admin->assignRole('administrator');

        // Create manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@levora.uz'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'agency_id' => $agency->id,
                'phone' => '+998712334456',
                'is_active' => true,
            ]
        );
        $manager->assignRole('manager');

        $this->command->info('Admin users seeded: admin@levora.uz / manager@levora.uz (password: password)');
    }
}
