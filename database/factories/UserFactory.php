<?php

namespace Database\Factories;

use App\Models\Mitra;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => Role::firstOrCreate(
                ['name' => 'super_admin'],
                ['label' => 'Super Administrator'],
            )->id,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function withRole(string $roleName): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)])->id,
        ]);
    }

    public function forMitra(Mitra $mitra): static
    {
        return $this->state(fn () => ['mitra_id' => $mitra->id]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
