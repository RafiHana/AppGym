<?php

namespace Database\Seeders;

use App\Models\PaketMember;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaketGym extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        PaketMember::create([
        'name' => 'Paket Bronze',
        'type' => 'bronze',
        'price' => 300000,
        'duration_months' => 3,
        'description' => 'Paket keanggotaan dasar untuk 3 bulan'
    ]);

    PaketMember::create([
        'name' => 'Paket Platinum',
        'type' => 'platinum',
        'price' => 600000,
        'duration_months' => 6,
        'description' => 'Paket keanggotaan premium untuk 6 bulan'
    ]);

    PaketMember::create([
        'name' => 'Paket Gold',
        'type' => 'gold',
        'price' => 1000000,
        'duration_months' => 12,
        'description' => 'Paket keanggotaan eksklusif untuk 1 tahun'
    ]);

    }
}
