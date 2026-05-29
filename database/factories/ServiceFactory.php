<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Consultation', 'Haircut', 'Therapy', 'Checkup']),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'image' => null,
            'price' => fake()->randomFloat(2, 50, 500),
            'description' => fake()->sentence(),
        ];
    }
}
