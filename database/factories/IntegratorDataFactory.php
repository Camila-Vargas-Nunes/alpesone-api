<?php

namespace Database\Factories;

use App\Models\IntegratorData;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegratorDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IntegratorData::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'data' => [
                'id' => $this->faker->unique()->numberBetween(1, 1000),
                'name' => $this->faker->company,
                'description' => $this->faker->sentence,
                'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
                'created_at' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s'),
                'updated_at' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s')
            ],
            'data_hash' => $this->faker->unique()->md5,
            'last_updated' => $this->faker->dateTimeThisYear(),
            'source_url' => 'https://hub.alpes.one/api/v1/integrator/export/1902'
        ];
    }
}
