<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestModels;

use byteit\LaravelExtendedStateMachines\Tests\database\factories\SalesOrderFactory;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelExtendedStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id,
 * @property string $notes,
 * @property int $total,
 *
 * @method \byteit\LaravelExtendedStateMachines\StateMachines\State state()
 * @method \byteit\LaravelExtendedStateMachines\StateMachines\State syncState()
 * @method \byteit\LaravelExtendedStateMachines\StateMachines\State asyncState()
 *
 * @method static SalesOrderFactory factory($count = null, $state = [])
 */
class SalesOrder extends Model
{
    use HasStateMachines;
    use HasFactory;

    protected $guarded = [];


    public $stateMachines = [
        'state' => State::class,
        'sync_state' => StateWithSyncAction::class,
        'async_state' => StateWithAsyncAction::class,
    ];

    protected static function newFactory(): SalesOrderFactory
    {
        return SalesOrderFactory::new();
    }

}
