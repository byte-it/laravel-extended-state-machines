<?php

use byteit\LaravelExtendedStateMachines\Jobs\PostponedTransitionExecutor;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;


it('should apply pending transition', function () {
    //Arrange
    $salesManager = SalesManager::factory()->create();

    $salesOrder = SalesOrder::factory()->create();

    $pendingTransition = $salesOrder->state()->postponeTransitionTo(
        State::Intermediate,
        Carbon::now(),
        ['comments' => 'All good!'],
        $salesManager
    );

    $this->assertTrue($salesOrder->state()->is(State::Init));

    $this->assertTrue($salesOrder->state()->hasPostponedTransitions());

    Queue::after(function (JobProcessed $event) {
        $this->assertFalse($event->job->hasFailed());
    });

    //Act
    (new PostponedTransitionExecutor($pendingTransition))->handle();

    //Assert
    $salesOrder->refresh();

    $this->assertTrue($salesOrder->state()->is(State::Intermediate));

    $this->assertEquals(
        'All good!',
        $salesOrder->state()->getCustomProperty('comments')
    );

    $this->assertEquals(
        $salesManager->id,
        $salesOrder->state()->responsible()->id
    );

    $this->assertFalse($salesOrder->state()->hasPostponedTransitions());
});
