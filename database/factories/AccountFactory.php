<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Account::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'currency' => $this->faker->randomElement(['USD', 'CDF']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
        ];
    }
}
