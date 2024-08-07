<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'headquarter_id' => 1,
            'user_id' => 1,
            'description' => $this->faker->text(100),
            'date' => $this->faker->date('Y-m-d'),
            'type_output' => $this->faker->text(50),
            'type_movement' => $this->faker->text(50),
            'price' => $this->faker->randomNumber(),
        ];
    }
}
