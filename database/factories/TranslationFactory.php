<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'test.' . $this->faker->unique()->word() . '.' . $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
            'locale' => $this->faker->randomElement(['en', 'fr', 'es', 'de', 'it']),
        ];
    }
} 