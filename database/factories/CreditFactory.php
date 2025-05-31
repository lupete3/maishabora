<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit>
 */
class CreditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Credit::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'currency' => $this->faker->randomElement(['USD', 'CDF']),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'interest_rate' => $this->faker->randomFloat(2, 2, 10),
            'installments' => $this->faker->numberBetween(3, 12),
            'start_date' => now(),
            'due_date' => now()->addMonths(6),
            'is_paid' => false,
        ];
    }
}
