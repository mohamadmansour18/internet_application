<?php

namespace Database\Seeders;

use App\Enums\AgencyName;
use App\Models\Agency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $code = 110 ;
        foreach (AgencyName::cases() as $agency)
        {
            $description = match($agency) {
                AgencyName::MINISTRY_OF_ELECTRICITY => 'الجهة المسؤولة عن تنظيم وإدارة قطاع الكهرباء' ,
                AgencyName::MINISTRY_OF_INTERIOR => 'الجهة المسؤولة عن الأمن الداخلي وإدارة الشرطة' ,
                AgencyName::MINISTRY_OF_DEFENSE => 'الجهة المسؤولة عن القوات المسلحة والدفاع',
            };

            Agency::updateOrCreate([
                'name' => $agency->value
            ] , [
                'code' => $code,
                'description' => $description,
            ]);

            $code++;
        }
    }
}
