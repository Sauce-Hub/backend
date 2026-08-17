<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\Suggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ingredient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'quantity' => fake()->randomFloat(2, 0.1, 10.0),
            'unit' => fake()->randomElement(['g', 'kg', 'ml', 'l', 'tsp', 'tbsp', 'cup', 'piece']),
            'isAssigned' => fake()->boolean(),
            'receipt_id' => Receipt::factory(),
            'suggestion_id' => null,
        ];
    }

    /**
     * Indicate that the ingredient is for a suggestion.
     */
    public function forSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_id' => null,
            'suggestion_id' => Suggestion::factory(),
        ]);
    }
}
