<?php

namespace Database\Factories;

use App\Models\Suggestion;
use App\Models\User;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suggestion>
 */
class SuggestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Suggestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'receipt_id' => Receipt::factory(),
            'text' => fake()->paragraph(),
            'isApproved' => fake()->boolean(),
        ];
    }
}
