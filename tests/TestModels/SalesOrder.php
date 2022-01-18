<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestModels;

use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStates;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use byteit\LaravelExtendedStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasStateMachines;

    protected $guarded = [];


    public $stateMachines = [
        'status' => StatusStates::class,
        'fulfillment' => FulfillmentStates::class,
    ];


}
