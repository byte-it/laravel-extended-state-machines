<?php

use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(PostponedTransition::class, function (Faker $faker) {
    return [
        'field' => $faker->word,
        'from' => $faker->word,
        'to' => $faker->word,

        'transition_at' => Carbon::tomorrow(),

        'model_id' => $faker->randomDigitNotNull,
        'model_type' => $faker->word,
    ];
});
