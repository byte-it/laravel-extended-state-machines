<?php

namespace byteit\LaravelExtendedStateMachines\Tests\database\factories;

use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesManagerFactory extends Factory
{

    protected $model = SalesManager::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];
    }

}
