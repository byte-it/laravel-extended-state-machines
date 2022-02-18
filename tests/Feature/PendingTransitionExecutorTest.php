<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Jobs\PostponedTransitionExecutor;
use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;

class PendingTransitionExecutorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function should_apply_pending_transition()
    {
        //Arrange
        $salesManager = factory(SalesManager::class)->create();

        $salesOrder = factory(SalesOrder::class)->create();

        $pendingTransition = $salesOrder->status()->postponeTransitionTo(
            StatusStates::Approved,
            Carbon::now(),
            ['comments' => 'All good!'],
            $salesManager
        );

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        $this->assertTrue($salesOrder->status()->hasPostponedTransitions());

        Queue::after(function (JobProcessed $event) {
            $this->assertFalse($event->job->hasFailed());
        });

        //Act
        PostponedTransitionExecutor::dispatch($pendingTransition);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Approved));

        $this->assertEquals('All good!', $salesOrder->status()->getCustomProperty('comments'));

        $this->assertEquals($salesManager->id, $salesOrder->status()->responsible()->id);

        $this->assertFalse($salesOrder->status()->hasPostponedTransitions());
    }

    /** @test */
    public function should_fail_job_automatically_if_starting_transition_is_not_the_same_as_when_postponed()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $salesOrder->status()->postponeTransitionTo(StatusStates::Approved, Carbon::now());

        //Manually update state
        $salesOrder->update(['status' => 'processed']);
        $this->assertTrue($salesOrder->status()->is(StatusStates::Processed));

        $this->assertTrue($salesOrder->status()->hasPostponedTransitions());

        Queue::after(function (JobProcessed $event) {
            $this->assertTrue($event->job->hasFailed());
        });

        //Act
        $pendingTransition = $salesOrder->status()->postponedTransitions()->first();

        PostponedTransitionExecutor::dispatch($pendingTransition);
    }
}
