<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\AfterTransitionJob;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithAfterTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithAfterTransitionHookStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;

class AfterTransitionHookTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function should_call_after_transition_hooks()
    {
        //Arrange
        Queue::fake();

        $salesOrder = SalesOrderWithAfterTransitionHook::create([
            'total' => 100,
            'notes' => 'before',
        ]);

        //Act
        $salesOrder->status()->transitionTo(StatusWithAfterTransitionHookStates::Approved);

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(200, $salesOrder->total);
        $this->assertEquals('after', $salesOrder->notes);

        Queue::assertPushed(AfterTransitionJob::class);
    }

    /** @test */
    public function should_not_call_after_transition_hooks_if_not_defined()
    {
        //Arrange
        Queue::fake();

        $salesOrder = SalesOrderWithAfterTransitionHook::create([
            'status' => StatusWithAfterTransitionHookStates::Approved
        ]);

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);
        //Act
        $salesOrder->status()->transitionTo(StatusWithAfterTransitionHookStates::Processed);

        //Assert
        $salesOrder->refresh();

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);

        Queue::assertNotPushed(AfterTransitionJob::class);
    }
}
