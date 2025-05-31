<?php

namespace Database\Factories;

use App\Models\Credit;
use App\Models\Repayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Repayment>
 */
class RepaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Repayment::class;

    public function definition()
    {
        // $expected = $this->faker->randomFloat(2, 100, 1000);
        // $penalty = $this->faker->boolean(20) ? $this->faker->randomFloat(2, 10, 100) : 0;

        return [
            // 'credit_id' => Credit::factory(),
            // 'due_date' => now()->addWeek(),
            // 'paid_date' => null,
            // 'expected_amount' => $expected,
            // 'penalty' => $penalty,
            // 'total_due' => $expected + $penalty,
            // 'paid_amount' => 0,
            // 'is_paid' => false,
        ];
    }
}
