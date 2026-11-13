<?php

namespace Database\Factories;

use App\Models\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;

/** ponytail: only fields tests need; add columns when business logic requires them. */
class MitraFactory extends Factory
{
    protected $model = Mitra::class;

    public function definition(): array
    {
        return [
            'kode_mitra' => 'MT-TEST-'.fake()->unique()->numerify('####'),
            'nama_mitra' => 'Mitra '.fake()->company(),
            'nama_pic' => fake()->name(),
            'no_hp' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'alamat' => fake()->address(),
            'status' => 'aktif',
        ];
    }
}
