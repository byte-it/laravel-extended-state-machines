<?php
namespace byteit\LaravelExtendedStateMachines\Tests\database\factories;

use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrderFactory extends Factory {

    protected $model = SalesOrder::class;

    public function definition()
    {
        return [];
    }



}
