<?php

namespace Database\Factories;

use App\Models\Severity;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeverityFactory extends Factory
{
    protected $model = Severity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
        ];
    }
}
