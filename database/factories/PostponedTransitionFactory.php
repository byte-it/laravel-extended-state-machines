<?php
namespace byteit\LaravelExtendedStateMachines\Database\Factories;

use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;


class PostponedTransitionFactory extends Factory{

    protected $model = PostponedTransition::class;

    public function definition()
    {
        return [
            'transition_at' => Carbon::now()->addMinutes(5),
        ];
    }

}

