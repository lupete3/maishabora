<?php

namespace Database\Factories;

use App\Models\AgentAccount;
use App\Models\MainCashRegister;
use App\Models\Transfert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transfert>
 */
class TransfertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Transfert::class;

    public function definition()
    {
        return [
            'from_agent_account_id' => AgentAccount::factory(),
            'to_main_cash_register_id' => MainCashRegister::factory(),
            'currency' => $this->faker->randomElement(['USD', 'CDF']),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
