<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->create([
            'name' => 'Monthly',
            'slug' => 'monthly',
            'description' => 'Monthly billing plan',
            'price_cents' => 999,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'billing_interval_count' => 1,
            'is_active' => true,
        ]);

        Plan::query()->create([
            'name' => 'Yearly',
            'slug' => 'yearly',
            'description' => 'Yearly billing plan',
            'price_cents' => 9999,
            'currency' => 'USD',
            'billing_interval' => 'yearly',
            'billing_interval_count' => 1,
            'is_active' => true,
        ]);
    }
}
