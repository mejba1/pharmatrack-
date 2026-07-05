<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TherapeuticClassSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Antibiotics / Antimicrobials', 'Analgesics / Pain Relief',
            'Anti-inflammatory / NSAIDs', 'Antidiabetics / Insulin',
            'Cardiovascular', 'Antihypertensives', 'Antifungals',
            'Antivirals', 'Antiparasitics', 'Respiratory / Bronchodilators',
            'CNS / Neurological', 'Gastrointestinal',
            'Endocrinology / Hormones', 'Vaccines / Immunologicals',
            'Vitamins / Supplements', 'Dermatology', 'Ophthalmology',
            'Oncology / Antineoplastics', 'Haematology', 'Musculoskeletal',
            'Urological', 'Other',
        ];

        foreach ($names as $name) {
            DB::table('therapeutic_classes')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
