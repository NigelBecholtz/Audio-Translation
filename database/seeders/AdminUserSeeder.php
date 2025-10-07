<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'support@optimasys.es',
            'password' => Hash::make('Artemis-123'),
            'is_admin' => true,
            'subscription_type' => 'free',
            'translations_used' => 0,
            'translations_limit' => 999, // Unlimited for admin
            'credits' => 999.00, // Unlimited credits for admin
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: support@optimasys.es');
        $this->command->info('Password: Artemis-123');
    }
}
