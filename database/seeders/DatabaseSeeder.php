<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lms.test'],
            ['name' => 'Admin', 'password' => 'password', 'role' => UserRole::Admin],
        );

        User::updateOrCreate(
            ['email' => 'student@lms.test'],
            ['name' => 'Student', 'password' => 'password', 'role' => UserRole::Student],
        );
    }
}
