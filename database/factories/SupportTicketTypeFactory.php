<?php

namespace Database\Factories;

use App\Models\SupportTicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketType>
 */
class SupportTicketTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str_replace(' ', '_', $name),
            'description' => fake()->sentence(),
            'color' => 'slate',
            'assignable' => true,
            'is_system' => false,
            'active' => true,
            'sort' => 0,
        ];
    }

    public function notAssignable(): static
    {
        return $this->state(['assignable' => false]);
    }
}
