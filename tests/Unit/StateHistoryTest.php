<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Unit;

use byteit\LaravelExtendedStateMachines\Models\Transition;
use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class StateHistoryTest extends TestCase
{

    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function can_get_custom_property(): void
    {
        //Arrange
        $comments = $this->faker->sentence;

        $stateHistory = factory(Transition::class)->create([
          'from' => StatusStates::Pending,
          'to' => StatusStates::Processed,
          'states' => StatusStates::class,
          'custom_properties' => [
            'comments' => $comments,
          ],
        ]);

        //Act
        $result = $stateHistory->getCustomProperty('comments');

        //Assert
        $this->assertEquals($comments, $result);
    }

    /** @test */
    public function can_get_all_custom_properties(): void
    {
        //Arrange
        $customProperties = [
          'amount' => $this->faker->numberBetween(1, 100),
          'comments' => $this->faker->sentence,
          'approved_by' => $this->faker->randomDigitNotNull,
        ];

        $stateHistory = factory(Transition::class)->create([
          'from' => StatusStates::Pending,
          'to' => StatusStates::Processed,
          'states' => StatusStates::class,
          'custom_properties' => $customProperties,
        ]);

        //Act
        $result = $stateHistory->allCustomProperties();

        //Assert
        $this->assertEquals($customProperties, $result);
    }

}
