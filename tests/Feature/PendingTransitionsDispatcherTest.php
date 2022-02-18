<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Jobs\PostponedTransitionExecutor;
use byteit\LaravelExtendedStateMachines\Jobs\PostponedTransitionsDispatcher;
use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;

class PostponedTransitionsDispatcherTest extends TestCase
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

        $postponed =
            $salesOrder->status()->postponeTransitionTo(StatusStates::Approved, Carbon::now()->subSecond());

        $this->assertTrue($salesOrder->status()->hasPostponedTransitions());

        //Act
        PostponedTransitionsDispatcher::dispatchNow();

        //Assert
        $salesOrder->refresh();

        $this->assertFalse($salesOrder->status()->hasPostponedTransitions());

        Queue::assertPushed(PostponedTransitionExecutor::class, function (PostponedTransitionExecutor $job) use ($postponed) {
            $this->assertEquals($postponed->id, $job->postponedTransition->id);
            return true;
        });
    }

    /** @test */
    public function should_not_dispatch_future_pending_transitions()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $salesOrder->status()->postponeTransitionTo(StatusStates::Approved, Carbon::tomorrow());

        $this->assertTrue($salesOrder->status()->hasPostponedTransitions());

        //Act
        PostponedTransitionsDispatcher::dispatchSync();

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->hasPostponedTransitions());

        Queue::assertNothingPushed();
    }
}
