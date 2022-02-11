<?php

use byteit\LaravelExtendedStateMachines\Models\Transition;
use Faker\Generator as Faker;

$factory->define(Transition::class, function (Faker $faker) {
    return [
        'field' => $faker->word,
        'from' => $faker->word,
        'to' => $faker->word,

        'model_id' => $faker->randomDigitNotNull,
        'model_type' => $faker->word,
    ];
});