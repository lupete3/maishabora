<?php

namespace Database\Factories;

use App\Models\AgentAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentAccount>
 */
class AgentAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = AgentAccount::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'currency' => $this->faker->randomElement(['USD', 'CDF']),
            'balance' => 0,
            'missing_or_surplus' => 0,
        ];
    }
}
