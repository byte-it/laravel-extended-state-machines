<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\BeforeTransitionJob;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;

class BeforeTransitionHookTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function should_call_before_transition_hooks()
    {
        //Arrange
        Queue::fake();

        $salesOrder = SalesOrderWithBeforeTransitionHook::create();

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);

        //Act
        $salesOrder->status()->transitionTo(StatusWithBeforeTransitionHookStates::Approved);

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(100, $salesOrder->total);
        $this->assertEquals('Notes updated', $salesOrder->notes);

        Queue::assertPushed(BeforeTransitionJob::class);
    }

    /** @test */
    public function should_not_call_before_transition_hooks_if_not_defined()
    {
        //Arrange
        Queue::fake();

        $salesOrder = SalesOrderWithBeforeTransitionHook::create([
            'status' => 'approved'
        ]);

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);

        //Act
        $salesOrder->status()->transitionTo(StatusWithBeforeTransitionHookStates::Processed);

        //Assert
        $salesOrder->refresh();

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);

        Queue::assertNotPushed(BeforeTransitionJob::class);
    }
}
