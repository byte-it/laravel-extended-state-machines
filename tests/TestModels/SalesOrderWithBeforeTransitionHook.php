<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestModels;

use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelExtendedStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrderWithBeforeTransitionHook extends Model
{
    use HasStateMachines;

    protected $table = 'sales_orders';

    protected $guarded = [];

    public $stateMachines = [
        'state' => State::class,
        'sync_state' => StateWithSyncAction::class,
        'async_state' => StateWithAsyncAction::class,
    ];
}
