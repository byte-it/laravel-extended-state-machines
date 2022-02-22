<?php

namespace byteit\LaravelExtendedStateMachines\Tests\Feature;

use byteit\LaravelExtendedStateMachines\Tests\TestCase;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStates;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class QueryScopesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function can_get_models_with_transition_responsible_model()
    {
        //Arrange
        $salesManager = factory(SalesManager::class)->create();

        $anotherSalesManager = factory(SalesManager::class)->create();

        factory(SalesOrder::class)->create()->status()->transitionTo(StatusStates::Approved, [], $salesManager);
        factory(SalesOrder::class)->create()->status()->transitionTo(StatusStates::Approved, [], $salesManager);
        factory(SalesOrder::class)->create()->status()->transitionTo(StatusStates::Approved, [], $anotherSalesManager);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) use ($salesManager) {
                $query->withResponsible($salesManager);
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(2, $salesOrders->count());

        $salesOrders->each(function (SalesOrder $salesOrder) use ($salesManager) {
            $this->assertEquals($salesManager->id, $salesOrder->status()->snapshotWhen(StatusStates::Approved)->responsible->id);
        });
    }

    /** @test */
    public function can_get_models_with_transition_responsible_id()
    {
        //Arrange
        $salesManager = factory(SalesManager::class)->create();

        $anotherSalesManager = factory(SalesManager::class)->create();

        factory(SalesOrder::class)->create()->status()->transitionTo(StatusStates::Approved, [], $salesManager);
        factory(SalesOrder::class)->create()->status()->transitionTo(StatusStates::Approved, [], $anotherSalesManager);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) use ($salesManager) {
                $query->withResponsible($salesManager->id);
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(1, $salesOrders->count());
    }

    /** @test */
    public function can_get_models_with_specific_transition()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();
        $salesOrder->status()->transitionTo(StatusStates::Approved);
        $salesOrder->fulfillment()->transitionTo(FulfillmentStates::Complete);
        $salesOrder->status()->transitionTo(StatusStates::Processed);

        $anotherSalesOrder = factory(SalesOrder::class)->create();
        $anotherSalesOrder->status()->transitionTo(StatusStates::Approved);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->withTransition(StatusStates::Approved, StatusStates::Processed);
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    /** @test */
    public function can_get_models_with_specific_transition_to_state()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();
        $salesOrder->status()->transitionTo(StatusStates::Approved);
        $salesOrder->status()->transitionTo(StatusStates::Processed);

        $anotherSalesOrder = factory(SalesOrder::class)->create();
        $anotherSalesOrder->status()->transitionTo(StatusStates::Approved);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->to(StatusStates::Processed);
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    /** @test */
    public function can_get_models_with_specific_transition_from_state()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();
        $salesOrder->status()->transitionTo(StatusStates::Approved);
        $salesOrder->status()->transitionTo(StatusStates::Processed);

        $anotherSalesOrder = factory(SalesOrder::class)->create();
        $anotherSalesOrder->status()->transitionTo(StatusStates::Approved);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->from(StatusStates::Approved);
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    /** @test */
    public function can_get_models_with_specific_transition_custom_property()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();
        $salesOrder->status()->transitionTo(StatusStates::Approved, ['comments' => 'Checked']);

        $anotherSalesOrder = factory(SalesOrder::class)->create();
        $anotherSalesOrder->status()->transitionTo(StatusStates::Approved, ['comments' => 'Needs further revision']);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->withCustomProperty('comments', 'like', '%Check%');
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    /** @test */
    public function can_get_models_using_multiple_state_machines_transitions()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();
        $salesOrder->status()->transitionTo(StatusStates::Approved);
        $salesOrder->status()->transitionTo(StatusStates::Processed);

        $anotherSalesOrder = factory(SalesOrder::class)->create();
        $anotherSalesOrder->status()->transitionTo(StatusStates::Approved);

        //Act


        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->to(StatusStates::Approved);
            })
            ->whereHasStatus(function ($query) {
                $query->to(StatusStates::Processed);
            })
            ->get()
        ;

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }
}
