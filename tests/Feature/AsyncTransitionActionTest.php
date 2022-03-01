<?php

use byteit\LaravelExtendedStateMachines\Jobs\TransitionAction;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\QueuedTransitionAction;
use Illuminate\Support\Facades\Queue;

test('The action job should be pushed on the queue', function (){
    $queue = Queue::fake();

    $salesOrder = SalesOrderWithBeforeTransitionHook::create();

    QueuedTransitionAction::$invoked = false;
    $salesOrder->async_state()->transitionTo(StateWithAsyncAction::AsyncAction);

    $queue->assertPushed(TransitionAction::class);

});

test('The action job should be run on the connection and queue of the action');
test('The action should be executed');
test('The pending transition should be marked as finished after the action has been executed');
test('The pending transition should be failed when the action handler fails');
test('The model attributes change should be recorded over the action runtime');
test('The model should be saved with the new state after the action has been executed');
test('The job should be pushed on the action');
test('The transition should be pushed on the action');
