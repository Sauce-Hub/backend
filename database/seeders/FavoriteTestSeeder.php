<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteTestSeeder extends Seeder
{
    public function run(): void
    {
        $ahmed = User::firstOrCreate(
            ['email' => 'ahmed@example.com'],
            [
                'name' => 'Ahmed',
                'password' => bcrypt('Password123!'),
            ]
        );

        $sara = User::firstOrCreate(
            ['email' => 'sara@example.com'],
            [
                'name' => 'Sara',
                'password' => bcrypt('Password123!'),
            ]
        );

        $receipt1 = Receipt::create([
            'name' => 'Pasta Primavera',
            'caption' => 'Healthy and quick dinner',
            'category' => 'DINNER',
            'estimated_time' => '25 mins',
            'Calories' => 520,
            'Fats' => 18,
            'Carbs' => 60,
            'Protein' => 22,
            'timestamp' => now(),
            'user_id' => $ahmed->user_id,
        ]);

        $receipt2 = Receipt::create([
            'name' => 'Chicken Bowl',
            'caption' => 'High protein lunch',
            'category' => 'LUNCH',
            'estimated_time' => '20 mins',
            'Calories' => 610,
            'Fats' => 24,
            'Carbs' => 48,
            'Protein' => 30,
            'timestamp' => now(),
            'user_id' => $ahmed->user_id,
        ]);

        $receipt3 = Receipt::create([
            'name' => 'Avocado Toast',
            'caption' => 'Breakfast favorite',
            'category' => 'BREAKFAST',
            'estimated_time' => '10 mins',
            'Calories' => 340,
            'Fats' => 14,
            'Carbs' => 28,
            'Protein' => 12,
            'timestamp' => now(),
            'user_id' => $sara->user_id,
        ]);

        Ingredient::create([
            'name' => 'Pasta',
            'quantity' => 200,
            'unit' => 'g',
            'isAssigned' => true,
            'receipt_id' => $receipt1->receipt_id,
        ]);

        Ingredient::create([
            'name' => 'Spinach',
            'quantity' => 80,
            'unit' => 'g',
            'isAssigned' => true,
            'receipt_id' => $receipt1->receipt_id,
        ]);

        Ingredient::create([
            'name' => 'Chicken',
            'quantity' => 180,
            'unit' => 'g',
            'isAssigned' => true,
            'receipt_id' => $receipt2->receipt_id,
        ]);

        $ahmed->favorites()->syncWithoutDetaching([$receipt1->receipt_id, $receipt2->receipt_id]);
        $sara->favorites()->syncWithoutDetaching([$receipt3->receipt_id]);
    }
}