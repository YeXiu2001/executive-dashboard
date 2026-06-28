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
        // User::factory(10)->create();

        User::query()->updateOrCreate(
            ['email' => 'test@perzeuscorp.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('Testpassword123'),
            ]
        );

        $this->call([
            AppLookupSeeder::class,
            GeneralFundRevenueSourceSeeder::class,
        ]);
    }
}
