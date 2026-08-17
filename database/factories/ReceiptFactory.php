<?php

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Receipt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'caption' => fake()->sentence(),
            'category' => fake()->randomElement(['BREAKFAST', 'LUNCH', 'DINNER', 'SWEETS', 'HOT DRINKS', 'ICED DRINKS']),
            'estimated_time' => fake()->randomElement(['15 mins', '30 mins', '1 hour']),
            'Calories' => fake()->numberBetween(100, 1000),
            'Fats' => fake()->numberBetween(0, 50),
            'Carbs' => fake()->numberBetween(0, 100),
            'Protein' => fake()->numberBetween(0, 50),
            'user_id' => User::factory(),
        ];
    }
}
