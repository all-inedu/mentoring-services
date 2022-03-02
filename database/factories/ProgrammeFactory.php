<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProgrammeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'prog_mod_id' => 1,
            'prog_name'   => $this->faker->words(3, true),
            'prog_desc'   => $this->faker->words(5, true),
            'status'      => 'active'
        ];
    }
}
