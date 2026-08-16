<?php

namespace Database\Factories;

use App\Models\Instruction;
use App\Models\Receipt;
use App\Models\Suggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instruction>
 */
class InstructionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Instruction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'step_number' => fake()->numberBetween(1, 10),
            'instruction' => fake()->sentence(),
            'receipt_id' => Receipt::factory(),
            'suggestion_id' => null,
        ];
    }

    /**
     * Indicate that the instruction is for a suggestion.
     */
    public function forSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_id' => null,
            'suggestion_id' => Suggestion::factory(),
        ]);
    }
}
