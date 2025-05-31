<?php

namespace Database\Factories;

use App\Models\MainCashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MainCashRegister>
 */
class MainCashRegisterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = MainCashRegister::class;

    public function definition()
    {
        return [
            'currency' => $this->faker->randomElement(['USD', 'CDF']),
            'balance' => $this->faker->randomFloat(2, 10000, 100000),
        ];
    }
}
