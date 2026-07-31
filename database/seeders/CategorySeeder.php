<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the fixed catalogue of skincare product categories.
     */
    public function run(): void
    {
        $categories = [
            'Huile nettoyante' => 'huile_nettoyante',
            'Gel nettoyant' => 'gel_nettoyant',
            'Ampoule' => 'ampoule',
            'Sérum' => 'serum',
            'Toner' => 'toner',
            'Essence' => 'essence',
            'Ampoule / Sérum' => 'ampoule_serum',
            'Beaume nettoyant' => 'beaume_nettoyant',
            'Mousse nettoyante' => 'mousse_nettoyante',
            'Masque' => 'masque',
            'Crème' => 'creme',
            'Exfoliant' => 'exfoliant',
            'Solaire' => 'solaire',
            'Yeux' => 'yeux',
            'Patch' => 'patch',
            'Brume' => 'brume',
            'Tonique' => 'tonique',
        ];

        foreach ($categories as $name => $slug) {
            Category::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
        }
    }
}
