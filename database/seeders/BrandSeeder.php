<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Seed the fixed catalogue of K-beauty brands.
     */
    public function run(): void
    {
        $brands = [
            'Abib' => 'abib',
            'Anua' => 'anua',
            'AXIS-Y' => 'axis-y',
            "A'PIEU" => 'apieu',
            'COSRX' => 'cosrx',
            'ESFOLIO' => 'esfolio',
            'ETUDE' => 'etude',
            'GOODAL' => 'goodal',
            'HARUHARU WONDER' => 'haruharu-wonder',
            'HEIMISH' => 'heimish',
            'ISNTREE' => 'isntree',
            'JUMISO' => 'jumiso',
            'LANEIGE' => 'laneige',
            'MARY&MAY' => 'marymay',
            'NEOGEN' => 'neogen',
            'PURITO' => 'purito',
            'SCINIC' => 'scinic',
            'SIORIS' => 'sioris',
            'SKIN1004' => 'skin1004',
            'SOME BY MI' => 'some-by-mi',
            'TOCOBO' => 'tocobo',
            'PYUNKANG YUL' => 'pyunkang-yul',
            'TORRIDEN' => 'torriden',
            'IUNIK' => 'iunik',
            'su:m7' => 'sum7',
            'MIZON' => 'mizon',
        ];

        foreach ($brands as $name => $slug) {
            Brand::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'country_of_origin' => 'Corée du Sud'],
            );
        }
    }
}
