<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Locale;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        Country::upsert([
            ['iso2' => 'MK', 'name_mk' => 'Северна Македонија', 'name_en' => 'North Macedonia'],
            ['iso2' => 'AL', 'name_mk' => 'Албанија', 'name_en' => 'Albania'],
            ['iso2' => 'RS', 'name_mk' => 'Србија', 'name_en' => 'Serbia'],
            ['iso2' => 'BG', 'name_mk' => 'Бугарија', 'name_en' => 'Bulgaria'],
            ['iso2' => 'GR', 'name_mk' => 'Грција', 'name_en' => 'Greece'],
            ['iso2' => 'XK', 'name_mk' => 'Косово', 'name_en' => 'Kosovo'],
            ['iso2' => 'TR', 'name_mk' => 'Турција', 'name_en' => 'Turkey'],
            ['iso2' => 'US', 'name_mk' => 'САД', 'name_en' => 'United States'],
            ['iso2' => 'DE', 'name_mk' => 'Германија', 'name_en' => 'Germany'],
        ], ['iso2'], ['name_mk', 'name_en']);

        Currency::upsert([
            ['code' => 'MKD', 'name_mk' => 'Денар', 'name_en' => 'Denar', 'minor_units' => 2],
            ['code' => 'EUR', 'name_mk' => 'Евро', 'name_en' => 'Euro', 'minor_units' => 2],
            ['code' => 'USD', 'name_mk' => 'Долар', 'name_en' => 'Dollar', 'minor_units' => 2],
            ['code' => 'ALL', 'name_mk' => 'Лек', 'name_en' => 'Lek', 'minor_units' => 2],
            ['code' => 'RSD', 'name_mk' => 'Динар', 'name_en' => 'Dinar', 'minor_units' => 2],
        ], ['code'], ['name_mk', 'name_en', 'minor_units']);

        Locale::upsert([
            ['code' => 'mk', 'name' => 'Македонски', 'is_default' => true],
            ['code' => 'en', 'name' => 'English', 'is_default' => false],
        ], ['code'], ['name', 'is_default']);
    }
}