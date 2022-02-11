<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Jobs\PendingTransitionExecutor;
use byteit\LaravelExtendedStateMachines\Jobs\PendingTransitionsDispatcher;
use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;

class PendingTransitionsDispatcherTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    /** @test */
    public function should_dispatch_pending_transition()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $pendingTransition =
            $salesOrder->status()->postponeTransitionTo(StatusStates::Approved, Carbon::now()->subSecond());

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        //Act
        PendingTransitionsDispatcher::dispatchNow();

        //Assert
        $salesOrder->refresh();

        $this->assertFalse($salesOrder->status()->hasPendingTransitions());

        Queue::assertPushed(PendingTransitionExecutor::class, function ($job) use ($pendingTransition) {
            $this->assertEquals($pendingTransition->id, $job->pendingTransition->id);
            return true;
        });
    }

    /** @test */
    public function should_not_dispatch_future_pending_transitions()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $salesOrder->status()->postponeTransitionTo(StatusStates::Approved, Carbon::tomorrow());

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        //Act
        PendingTransitionsDispatcher::dispatchSync();

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        Queue::assertNothingPushed();
    }
}
