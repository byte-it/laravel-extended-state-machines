<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\StateMachines\StateMachine;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use Carbon\Carbon;


it('can configure state machines', function (): void {
    //Act
    $salesOrder = SalesOrder::factory()->create();

    $this->assertEquals(StateWithSyncAction::class,
        $salesOrder->stateMachines['sync_state']);
    $this->assertEquals(StateWithAsyncAction::class,
        $salesOrder->stateMachines['async_state']);

    //Assert
    $this->assertNotNull($salesOrder->syncState());

    $this->assertNotNull($salesOrder->asyncState());
});


it('should set default state for field', function (): void {
    $salesOrder = SalesOrder::factory()->create();

    //Arrange
    $statusStateMachine = new StateMachine(
        StateWithSyncAction::class
    );

    $fulfillmentStateMachine = new StateMachine(
        StateWithAsyncAction::class
    );

    //Act

    //Assert
    $this->assertEquals(
        $statusStateMachine->defaultState(),
        $salesOrder->sync_state
    );
    $this->assertEquals(
        $statusStateMachine->defaultState(),
        $salesOrder->syncState()->state
    );

    $this->assertEquals(
        1,
        $salesOrder->syncState()->history()->count()
    );

    $this->assertEquals(
        $fulfillmentStateMachine->defaultState(),
        $salesOrder->async_state
    );

    $this->assertEquals(
        $fulfillmentStateMachine->defaultState(),
        $salesOrder->asyncState()->state
    );

    $this->assertEquals(
        1,
        $salesOrder->asyncState()->history()->count()
    );
});


it('should transition to next state', function (): void {
    /**
     * @todo Create proper model without actions
     */
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $this->assertTrue($salesOrder->syncState()
        ->is(StateWithSyncAction::Created));

    $this->assertEquals(StateWithSyncAction::Created,
        $salesOrder->sync_state);

    //Act
    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

    //Assert
    $salesOrder->refresh();

    $this->assertTrue($salesOrder->syncState()
        ->is(StateWithSyncAction::SyncAction));

    $this->assertEquals(StateWithSyncAction::SyncAction,
        $salesOrder->sync_state);
});

it('should register responsible for transition when specified',
    function (): void {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $salesOrder = SalesOrder::factory()->create();

        //Act
        $salesOrder->syncState()
            ->transitionTo(StateWithSyncAction::SyncAction, [], $salesManager);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->syncState()->responsible();

        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));

        $responsible = $salesOrder->syncState()
            ->snapshotWhen(StateWithSyncAction::SyncAction)->responsible;
        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));
    });


it('should register auth as responsible for transition when available',
    function (): void {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $this->actingAs($salesManager);

        $salesOrder = SalesOrder::factory()->create();

        //Act
        $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->syncState()->responsible();

        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));
    });


it('can check next possible transitions', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $this->assertTrue($salesOrder->syncState()
        ->is(StateWithSyncAction::Created));

    //Act - Assert
    $this->assertTrue($salesOrder->syncState()
        ->canBe(StateWithSyncAction::SyncAction));

    $this->assertFalse($salesOrder->syncState()
        ->canBe(StateWithSyncAction::Created));
});


it('should throw exception for invalid state on transition', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $this->assertFalse($salesOrder->syncState()
        ->canBe(StateWithSyncAction::Created));

    $this->expectException(TransitionNotAllowedException::class);
    $salesOrder->syncState()->transitionTo(StateWithSyncAction::Created);

});


it('should throw exception for class guard on transition', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $this->assertTrue($salesOrder->state()
        ->is(State::Init));


    $this->assertTrue($salesOrder->state()
        ->canBe(State::Intermediate));

    $this->expectException(TransitionGuardException::class);
    $salesOrder->state()->transitionTo(State::Guarded);

});

it('should throw exception for inline guard on transition', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $this->assertTrue($salesOrder->state()
        ->is(State::Init));


    $this->assertTrue($salesOrder->state()
        ->canBe(State::Intermediate));

    $this->expectException(TransitionGuardException::class);
    $salesOrder->state()->transitionTo(State::InlineGuarded);

});


it('should record history when transitioning to next state', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $this->assertTrue($salesOrder->syncState()
        ->stateMachine()
        ->recordHistory());

    $this->assertEquals(1, $salesOrder->syncState()->history()->count());

    //Act
    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

    //Assert
    $salesOrder->refresh();

    $this->assertEquals(2, $salesOrder->syncState()->history()->count());
});


it('should record history when creating model', function (): void {
    //Act
    $salesOrder = SalesOrder::factory()->create();

    //Assert
    $salesOrder->refresh();

    $this->assertEquals(1, $salesOrder->syncState()->history()->count());
});


it('should save auth user as responsible in record history when creating model',
    function (): void {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $this->actingAs($salesManager);

        //Act
        $salesOrder = SalesOrder::factory()->create();

        //Assert
        $salesOrder->refresh();

        $this->assertEquals($salesManager->id,
            $salesOrder->syncState()->responsible()->id);
    });


it('can record history with custom properties when transitioning to next state',
    function (): void {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        //Act
        $comments = 'Test';

        $salesOrder
            ->state()
            ->transitionTo(
                State::Intermediate,
                ['comments' => $comments]
            );

        //Assert
        $salesOrder->refresh();

        $this->assertTrue(
            $salesOrder
                ->state()
                ->is(State::Intermediate)
        );

        $this->assertEquals(
            $comments,
            $salesOrder->state()->getCustomProperty('comments')
        );
    });


it('can check if previous state was transitioned', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    //Act
    $salesOrder->state()->transitionTo(State::Intermediate);

    $salesOrder->state()->transitionTo(State::Finished);

    //Assert
    $salesOrder->refresh();

    $this->assertTrue(
        $salesOrder
            ->state()
            ->was(State::Intermediate)
    );

    $this->assertTrue(
        $salesOrder
            ->state()
            ->was(State::Finished)
    );

    $this->assertEquals(
        1,
        $salesOrder->state()->timesWas(State::Intermediate)
    );

    $this->assertEquals(
        1,
        $salesOrder->state()->timesWas(State::Finished)
    );

    $this->assertNotNull(
        $salesOrder
            ->state()
            ->whenWas(State::Intermediate)
    );

    $this->assertNotNull(
        $salesOrder
            ->state()
            ->whenWas(State::Finished)
    );

});


it('can record postponed transition', function (): void {
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $salesManager = SalesManager::factory()->create();

    //Act
    $customProperties = [
        'comments' => 'test',
    ];

    $responsible = $salesManager;

    $postponedTransition = $salesOrder->syncState()->postponeTransitionTo(
        StateWithSyncAction::SyncAction,
        Carbon::tomorrow()->startOfDay(),
        $customProperties,
        $responsible
    );

    //Assert
    $this->assertNotNull($postponedTransition);

    $salesOrder->refresh();

    $this->assertTrue($salesOrder->syncState()
        ->is(StateWithSyncAction::Created));

    $this->assertTrue($salesOrder->syncState()->hasPostponedTransitions());

    /** @var \byteit\LaravelExtendedStateMachines\Models\PostponedTransition $postponedTransition */
    $postponedTransition = $salesOrder->syncState()
        ->postponedTransitions()
        ->first();

    $this->assertEquals('sync_state', $postponedTransition->field);

    $this->assertEquals(StateWithSyncAction::Created,
        $postponedTransition->from);
    $this->assertEquals(StateWithSyncAction::SyncAction,
        $postponedTransition->to);

    $this->assertEquals(Carbon::tomorrow()->startOfDay(),
        $postponedTransition->transition_at);

    $this->assertEquals($customProperties,
        $postponedTransition->custom_properties);

    $this->assertNull($postponedTransition->applied_at);

    $this->assertEquals($salesOrder->id, $postponedTransition->model->id);

    $this->assertEquals($salesManager->id,
        $postponedTransition->responsible->id);
});


it('should throw exception for invalid state on postponed transition',
    function () {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->expectException(TransitionNotAllowedException::class);

        $salesOrder->state()->postponeTransitionTo(
            State::Finished,
            Carbon::tomorrow()
        );

    });

