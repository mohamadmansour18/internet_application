<?php

namespace Database\Seeders;

use App\Enums\ComplaintTypeName;
use App\Models\ComplaintType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComplaintTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ComplaintTypeName::cases() as $case)
        {
            ComplaintType::updateOrCreate([
                'name' => $case->value
            ]);
        }
    }
}
