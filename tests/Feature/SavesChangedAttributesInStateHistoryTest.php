<?php

use byteit\LaravelExtendedStateMachines\Models\Transition;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;


it('should_save_changed_attributes_when_transitioning_state', function () {
    //Arrange
    $salesOrder = SalesOrderWithBeforeTransitionHook::create([
        'total' => 100,
        'notes' => 'some notes',
    ]);

    //Act
    $salesOrder->refresh();

    $salesOrder->total = 200;
    $salesOrder->notes = 'other text';

    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

    //Assert
    $salesOrder->refresh();

    /** @var Transition $lastStateTransition */
    $lastStateTransition = $salesOrder->syncState()->history()->get()->last();

    $this->assertContains('notes',
        $lastStateTransition->changedAttributesNames());
    $this->assertContains('total',
        $lastStateTransition->changedAttributesNames());
    $this->assertContains('sync_state',
        $lastStateTransition->changedAttributesNames());

    $this->assertEquals('some notes',
        $lastStateTransition->changedAttributeOldValue('notes'));
    $this->assertEquals('other text',
        $lastStateTransition->changedAttributeNewValue('notes'));

    $this->assertEquals(100,
        $lastStateTransition->changedAttributeOldValue('total'));
    $this->assertEquals(200,
        $lastStateTransition->changedAttributeNewValue('total'));

    $this->assertEquals('created',
        $lastStateTransition->changedAttributeOldValue('sync_state'));
    $this->assertEquals(StateWithSyncAction::SyncAction->value,
        $lastStateTransition->changedAttributeNewValue('sync_state'));
});

it('should_save_changed_attributes_on_initial_state', function () {
    //Act
    $salesOrder = SalesOrder::create([
        'total' => 300,
        'notes' => 'initial notes',
    ]);

    //Assert
    $salesOrder->refresh();

    /** @var Transition $lastStateTransition */
    $lastStateTransition = $salesOrder->syncState()->history()->first();

    $this->assertContains('notes',
        $lastStateTransition->changedAttributesNames());
    $this->assertContains('total',
        $lastStateTransition->changedAttributesNames());
    $this->assertContains('sync_state',
        $lastStateTransition->changedAttributesNames());

    $this->assertEquals(null,
        $lastStateTransition->changedAttributeOldValue('notes'));
    $this->assertEquals('initial notes',
        $lastStateTransition->changedAttributeNewValue('notes'));

    $this->assertEquals(null,
        $lastStateTransition->changedAttributeOldValue('total'));
    $this->assertEquals(300,
        $lastStateTransition->changedAttributeNewValue('total'));

    $this->assertEquals(null,
        $lastStateTransition->changedAttributeOldValue('sync_state'));
    $this->assertEquals(StateWithSyncAction::Created->value,
        $lastStateTransition->changedAttributeNewValue('sync_state'));
});
