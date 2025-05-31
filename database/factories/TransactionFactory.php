<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Transaction::class;

    public function definition()
    {
        $type = $this->faker->randomElement([
            'deposit', 'withdrawal', 'credit', 'repayment',
            'transfer_to_central', 'surplus', 'missing'
        ]);

        return [
            'account_id' => Account::factory(),
            'agent_account_id' => null,
            'user_id' => User::factory(),
            'credit_id' => null,
            'type' => $type,
            'currency' => $this->faker->randomElement(['USD', 'CDF']),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'balance_after' => $this->faker->randomFloat(2, 0, 10000),
            'description' => $this->faker->sentence(),
        ];
    }
}
