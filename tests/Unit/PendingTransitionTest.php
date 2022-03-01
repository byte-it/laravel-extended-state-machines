<?php


use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\StateMachine;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;

it('can be serialized', function (){

    $order = SalesOrder::factory()->create();

    $stateMachine = new StateMachine(State::class);
    $transition = new PendingTransition($stateMachine, State::Init, State::Intermediate, $order, 'state', [], null);

    $serialized = serialize($transition);

    $order->notes = 'test note';
    $order->save();

    /** @var PendingTransition $woken */
    $woken = unserialize($serialized);


   $this->assertEquals($order->id, $woken->model->id);
   // Check that a fresh version of the model has been loaded
   $this->assertEquals($order->notes, $woken->model->notes);


});

it('can postpone a transition');
it('can be dispatched');
it('can finish a transition');
