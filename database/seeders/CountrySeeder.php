<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // [ISO alpha-2 code, name, region, dial code]
        $countries = [
            ['US', 'United States', 'North America', '+1'],
            ['CA', 'Canada', 'North America', '+1'],
            ['MX', 'Mexico', 'North America', '+52'],
            ['BR', 'Brazil', 'South America', '+55'],
            ['AR', 'Argentina', 'South America', '+54'],
            ['CL', 'Chile', 'South America', '+56'],
            ['CO', 'Colombia', 'South America', '+57'],
            ['GB', 'United Kingdom', 'Europe', '+44'],
            ['IE', 'Ireland', 'Europe', '+353'],
            ['FR', 'France', 'Europe', '+33'],
            ['DE', 'Germany', 'Europe', '+49'],
            ['ES', 'Spain', 'Europe', '+34'],
            ['PT', 'Portugal', 'Europe', '+351'],
            ['IT', 'Italy', 'Europe', '+39'],
            ['NL', 'Netherlands', 'Europe', '+31'],
            ['BE', 'Belgium', 'Europe', '+32'],
            ['CH', 'Switzerland', 'Europe', '+41'],
            ['AT', 'Austria', 'Europe', '+43'],
            ['SE', 'Sweden', 'Europe', '+46'],
            ['NO', 'Norway', 'Europe', '+47'],
            ['DK', 'Denmark', 'Europe', '+45'],
            ['FI', 'Finland', 'Europe', '+358'],
            ['PL', 'Poland', 'Europe', '+48'],
            ['CZ', 'Czechia', 'Europe', '+420'],
            ['GR', 'Greece', 'Europe', '+30'],
            ['RU', 'Russia', 'Europe', '+7'],
            ['TR', 'Turkey', 'Europe', '+90'],
            ['ZA', 'South Africa', 'Africa', '+27'],
            ['NG', 'Nigeria', 'Africa', '+234'],
            ['KE', 'Kenya', 'Africa', '+254'],
            ['EG', 'Egypt', 'Africa', '+20'],
            ['GH', 'Ghana', 'Africa', '+233'],
            ['ET', 'Ethiopia', 'Africa', '+251'],
            ['TZ', 'Tanzania', 'Africa', '+255'],
            ['MA', 'Morocco', 'Africa', '+212'],
            ['SA', 'Saudi Arabia', 'Middle East', '+966'],
            ['AE', 'United Arab Emirates', 'Middle East', '+971'],
            ['IL', 'Israel', 'Middle East', '+972'],
            ['JO', 'Jordan', 'Middle East', '+962'],
            ['IN', 'India', 'Asia', '+91'],
            ['PK', 'Pakistan', 'Asia', '+92'],
            ['BD', 'Bangladesh', 'Asia', '+880'],
            ['LK', 'Sri Lanka', 'Asia', '+94'],
            ['CN', 'China', 'Asia', '+86'],
            ['JP', 'Japan', 'Asia', '+81'],
            ['KR', 'South Korea', 'Asia', '+82'],
            ['ID', 'Indonesia', 'Asia', '+62'],
            ['MY', 'Malaysia', 'Asia', '+60'],
            ['SG', 'Singapore', 'Asia', '+65'],
            ['TH', 'Thailand', 'Asia', '+66'],
            ['VN', 'Vietnam', 'Asia', '+84'],
            ['PH', 'Philippines', 'Asia', '+63'],
            ['AU', 'Australia', 'Oceania', '+61'],
            ['NZ', 'New Zealand', 'Oceania', '+64'],
        ];

        foreach ($countries as [$code, $name, $region, $dial]) {
            DB::table('countries')->updateOrInsert(
                ['code' => $code],
                [
                    'name'              => $name,
                    'region'            => $region,
                    'flag'              => self::flagEmoji($code),
                    'dial_code'         => $dial,
                    'regulatory_status' => 'approved',
                    'is_active'         => true,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );
        }
    }

    /**
     * Build a flag emoji from a 2-letter ISO code using regional indicators.
     */
    public static function flagEmoji(string $code): ?string
    {
        $code = strtoupper($code);
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }
        $a = mb_convert_encoding('&#' . (127397 + ord($code[0])) . ';', 'UTF-8', 'HTML-ENTITIES');
        $b = mb_convert_encoding('&#' . (127397 + ord($code[1])) . ';', 'UTF-8', 'HTML-ENTITIES');

        return $a . $b;
    }
}
