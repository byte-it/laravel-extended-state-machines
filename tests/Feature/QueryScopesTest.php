<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;


it('can get models with transition responsible model', function () {
    //Arrange
    $salesManager =SalesManager::factory()->create();

    $anotherSalesManager =SalesManager::factory()->create();

    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(State::Intermediate, [], $salesManager);
    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(State::Intermediate, [], $salesManager);
    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(State::Intermediate, [], $anotherSalesManager);

    //Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) use ($salesManager) {
            $query->withResponsible($salesManager);
        })
        ->get();

    //Assert
    $this->assertEquals(2, $salesOrders->count());

    $salesOrders->each(function (SalesOrder $salesOrder) use ($salesManager) {
        $this->assertEquals($salesManager->id, $salesOrder->state()
            ->snapshotWhen(State::Intermediate)->responsible->id);
    });
});

it('can get models with transition responsible id', function () {
    //Arrange
    $salesManager =SalesManager::factory()->create();

    $anotherSalesManager =SalesManager::factory()->create();

    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(State::Intermediate, [], $salesManager);
    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(State::Intermediate, [], $anotherSalesManager);

    //Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) use ($salesManager) {
            $query->withResponsible($salesManager->id);
        })
        ->get();

    //Assert
    $this->assertEquals(1, $salesOrders->count());
});

it('can get models with specific transition', function () {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(State::Intermediate);
    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);
    $salesOrder->state()->transitionTo(State::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(State::Intermediate);

    //Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->withTransition(State::Intermediate,
                State::Finished);
        })
        ->get();

    //Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
});

it('can get models with specific transition to state', function () {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(State::Intermediate);
    $salesOrder->state()->transitionTo(State::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(State::Intermediate);

    //Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->to(State::Finished);
        })
        ->get();

    //Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
});

it('can get models with specific transition from state', function () {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(State::Intermediate);
    $salesOrder->state()->transitionTo(State::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(State::Intermediate);

    //Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->from(State::Intermediate);
        })
        ->get();

    //Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
});

it('can get models with specific transition custom property', function () {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()
        ->transitionTo(State::Intermediate, ['comments' => 'Checked']);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()
        ->transitionTo(State::Intermediate,
            ['comments' => 'Needs further revision']);

    //Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->withCustomProperty('comments', 'like', '%Check%');
        })
        ->get();

    //Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
});

it('can get models using multiple state machines transitions', function () {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(State::Intermediate);
    $salesOrder->state()->transitionTo(State::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(State::Intermediate);

    //Act


    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->to(State::Intermediate);
        })
        ->whereHasState(function ($query) {
            $query->to(State::Finished);
        })
        ->get();

    //Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
});
