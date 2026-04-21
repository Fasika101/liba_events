<?php

namespace Database\Seeders;

use App\Models\Company;
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
        $company = Company::query()->first()
            ?? Company::factory()->create(['name' => 'Demo company']);

        User::factory()
            ->admin()
            ->create([
                'company_id' => $company->id,
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'password',
            ]);

        User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => 'password',
        ]);

        
    }
}
