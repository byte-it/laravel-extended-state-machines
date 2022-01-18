<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\Models\PendingTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\StateMachine;
use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStates;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Queue;
use Throwable;

class HasStateMachinesTest extends TestCase
{

    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function can_configure_state_machines(): void
    {
        //Act
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertEquals(StatusStates::class,
          $salesOrder->stateMachines['status']);
        $this->assertEquals(FulfillmentStates::class,
          $salesOrder->stateMachines['fulfillment']);

        //Assert
        $this->assertNotNull($salesOrder->status());

        $this->assertNotNull($salesOrder->fulfillment());
    }

    /** @test */
    public function should_set_default_state_for_field(): void
    {
        $salesOrder = factory(SalesOrder::class)->create();

        //Arrange
        $statusStateMachine = new StateMachine('status', $salesOrder,
          StatusStates::class);

        $fulfillmentStateMachine = new StateMachine('fulfillment', $salesOrder,
          FulfillmentStates::class);

        //Act

        //Assert
        $this->assertEquals($statusStateMachine->defaultState(),
          $salesOrder->status);
        $this->assertEquals($statusStateMachine->defaultState(),
          $salesOrder->status()->state);
        $this->assertEquals(1, $salesOrder->status()->history()->count());

        $this->assertEquals($fulfillmentStateMachine->defaultState(),
          $salesOrder->fulfillment);
        $this->assertEquals($fulfillmentStateMachine->defaultState(),
          $salesOrder->fulfillment()->state);
        $this->assertEquals(0, $salesOrder->fulfillment()->history()->count());
    }

    /** @test */
    public function should_transition_to_next_state(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        $this->assertEquals(StatusStates::Pending, $salesOrder->status);

        //Act
        $salesOrder->status()->transitionTo(StatusStates::Approved);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Approved));

        $this->assertEquals(StatusStates::Approved, $salesOrder->status);
    }

    /** @test */
    public function should_not_do_anything_when_transitioning_to_same_state(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        $this->assertEquals(1, $salesOrder->status()->history()->count());

        //Act
        $salesOrder->status()->transitionTo(StatusStates::Pending);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        $this->assertEquals(1, $salesOrder->status()->history()->count());
    }

    /** @test */
    public function should_register_responsible_for_transition_when_specified(): void
    {
        //Arrange
        $salesManager = factory(SalesManager::class)->create();

        $salesOrder = factory(SalesOrder::class)->create();

        //Act
        $salesOrder->status()
          ->transitionTo(StatusStates::Approved, [], $salesManager);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->status()->responsible();

        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));

        $responsible = $salesOrder->status()
          ->snapshotWhen(StatusStates::Approved)->responsible;
        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));
    }

    /** @test */
    public function should_register_auth_as_responsible_for_transition_when_available(
    ): void
    {
        //Arrange
        $salesManager = factory(SalesManager::class)->create();

        $this->actingAs($salesManager);

        $salesOrder = factory(SalesOrder::class)->create();

        //Act
        $salesOrder->status()->transitionTo(StatusStates::Approved);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->status()->responsible();

        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));
    }

    /** @test */
    public function can_check_next_possible_transitions(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        //Act - Assert
        $this->assertTrue($salesOrder->status()->canBe(StatusStates::Approved));

        // @todo see how to handle this
        //        $this->assertFalse($salesOrder->status()->canBe('declined'));
    }

    /** @test */
    public function should_throw_exception_for_invalid_state_on_transition(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create([
          'status' => StatusStates::Approved,
        ]);

        $this->assertFalse($salesOrder->status()->canBe(StatusStates::Pending));

        //Act
        try {
            $salesOrder->status()->transitionTo(StatusStates::Pending);
            $this->fail('Should have thrown exception');
        } catch (Throwable $throwable) {
            //Assert
            $this->assertTrue($throwable instanceof TransitionNotAllowedException);
        }
    }

    /** @test */
    public function should_throw_exception_for_custom_validator_on_transition(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));


        $this->assertTrue($salesOrder->fulfillment()
          ->canBe(StatusStates::Pending));

        //Act
        try {
            $salesOrder->fulfillment()->transitionTo(StatusStates::Pending);
            $this->fail('Should have thrown exception');
        } catch (Throwable $throwable) {
            // Assert
            $this->assertTrue($throwable instanceof ValidationException);
        }
    }

    /** @test */
    public function should_record_history_when_transitioning_to_next_state(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertTrue($salesOrder->status()
          ->stateMachine()
          ->recordHistory());

        $this->assertEquals(1, $salesOrder->status()->history()->count());

        //Act
        $salesOrder->status()->transitionTo(StatusStates::Approved);

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(2, $salesOrder->status()->history()->count());
    }

    /** @test */
    public function should_record_history_when_creating_model(): void
    {
        //Arrange
        $dummySalesOrder = new SalesOrder();

        $stateMachine = new StateMachine('status', $dummySalesOrder,
          StatusStates::class);

        $this->assertTrue($stateMachine->recordHistory());

        //Act
        $salesOrder = factory(SalesOrder::class)->create();

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(1, $salesOrder->status()->history()->count());
    }

    /** @test */
    public function should_save_auth_user_as_responsible_in_record_history_when_creating_model(
    ): void
    {
        //Arrange
        $salesManager = factory(SalesManager::class)->create();

        $this->actingAs($salesManager);

        //Act
        $salesOrder = factory(SalesOrder::class)->create();

        //Assert
        $salesOrder->refresh();

        $this->assertEquals($salesManager->id,
          $salesOrder->status()->responsible()->id);
    }

    /** @test */
    public function should_not_record_history_when_creating_model_if_record_history_turned_off(
    ): void
    {
        //Arrange
        $dummySalesOrder = new SalesOrder();

        $stateMachine = new StateMachine('fulfillment', $dummySalesOrder,
          FulfillmentStates::class);

        $this->assertFalse($stateMachine->recordHistory());

        //Act
        $salesOrder = factory(SalesOrder::class)->create([
          'fulfillment' => FulfillmentStates::Pending,
        ]);

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(0, $salesOrder->fulfillment()->history()->count());
    }

    /** @test */
    public function can_record_history_with_custom_properties_when_transitioning_to_next_state(
    ): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        //Act
        $comments = $this->faker->sentence;

        $salesOrder->status()->transitionTo(StatusStates::Approved, [
          'comments' => $comments,
        ]);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Approved));

        $this->assertEquals($comments,
          $salesOrder->status()->getCustomProperty('comments'));
    }

    /** @test */
    public function can_check_if_previous_state_was_transitioned(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        //Act
        $salesOrder->status()->transitionTo(StatusStates::Approved);

        $salesOrder->status()->transitionTo(StatusStates::Processed);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->was(StatusStates::Approved));
        $this->assertTrue($salesOrder->status()->was(StatusStates::Processed));

        $this->assertEquals(1,
          $salesOrder->status()->timesWas(StatusStates::Approved));
        $this->assertEquals(1,
          $salesOrder->status()->timesWas(StatusStates::Processed));

        $this->assertNotNull($salesOrder->status()
          ->whenWas(StatusStates::Approved));
        $this->assertNotNull($salesOrder->status()
          ->whenWas(StatusStates::Processed));

    }

    /** @test */
    public function can_record_pending_transition(): void
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $salesManager = factory(SalesManager::class)->create();

        //Act
        $customProperties = [
          'comments' => $this->faker->sentence,
        ];

        $responsible = $salesManager;

        $pendingTransition = $salesOrder->status()->postponeTransitionTo(
          StatusStates::Approved,
          Carbon::tomorrow()->startOfDay(),
          $customProperties,
          $responsible
        );

        //Assert
        $this->assertNotNull($pendingTransition);

        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        /** @var PendingTransition $pendingTransition */
        $pendingTransition = $salesOrder->status()
          ->pendingTransitions()
          ->first();

        $this->assertEquals('status', $pendingTransition->field);

        $this->assertEquals(StatusStates::Pending,
         $pendingTransition->from);
        $this->assertEquals(StatusStates::Approved,
         $pendingTransition->to);

        $this->assertEquals(Carbon::tomorrow()->startOfDay(),
          $pendingTransition->transition_at);

        $this->assertEquals($customProperties,
          $pendingTransition->custom_properties);

        $this->assertNull($pendingTransition->applied_at);

        $this->assertEquals($salesOrder->id, $pendingTransition->model->id);

        $this->assertEquals($salesManager->id,
          $pendingTransition->responsible->id);
    }

    /** @test */
    public function should_not_record_pending_transition_for_same_state()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $this->assertTrue($salesOrder->status()->is(StatusStates::Pending));

        //Act
        $pendingTransition = $salesOrder->status()->postponeTransitionTo(
          StatusStates::Pending,
          Carbon::tomorrow()->startOfDay()
        );

        //Assert
        $this->assertNull($pendingTransition);
    }

    /** @test */
    public function should_cancel_all_pending_transitions_when_transitioning_to_next_state(
    )
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        factory(PendingTransition::class)->times(5)->create([
          'field' => 'status',
          'model_id' => $salesOrder->id,
          'model_type' => SalesOrder::class,
          'states' => FulfillmentStates::class,
          'from' => FulfillmentStates::Pending,
            'to' => FulfillmentStates::Partial,
        ]);

        factory(PendingTransition::class)->times(5)->create([
          'field' => 'fulfillment',
          'model_id' => $salesOrder->id,
          'model_type' => SalesOrder::class,
          'states' => FulfillmentStates::class,
          'from' => FulfillmentStates::Pending,
          'to' => FulfillmentStates::Partial,
        ]);

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());
        $this->assertTrue($salesOrder->fulfillment()->hasPendingTransitions());

        //Act
        $salesOrder->status()->transitionTo(StatusStates::Approved);

        //Assert
        $salesOrder->refresh();

        $this->assertFalse($salesOrder->status()->hasPendingTransitions());
        $this->assertTrue($salesOrder->fulfillment()->hasPendingTransitions());
    }

    /** @test */
    public function should_throw_exception_for_invalid_state_on_postponed_transition(
    )
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        //Act
        try {
            $salesOrder->status()
              ->postponeTransitionTo('invalid', Carbon::tomorrow());
            $this->fail('Should have thrown exception');
        } catch (Throwable $exception) {
            //Assert
            $this->assertTrue($exception instanceof TransitionNotAllowedException);
        }
    }

}
