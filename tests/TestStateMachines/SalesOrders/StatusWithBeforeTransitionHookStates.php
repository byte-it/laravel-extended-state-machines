<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;


use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\AddNotes;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\DispatchJob;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\UpdateOrderTotal;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\ProcessAction;

#[
  RecordHistory,
  HasActions([UpdateOrderTotal::class, AddNotes::class, DispatchJob::class])
]

enum StatusWithBeforeTransitionHookStates: string implements States
{
    case Pending = 'pending';
    case Approved =  'approved';
    case Processed = 'processed';

    public function transitions(): array
    {
        return match($this){
            self::Pending => [self::Approved],
            self::Approved => [self::Processed],
            default => []
        };
    }


//
//    public function beforeTransitionHooks(): array
//    {
//        return [
//            'pending' => [
//                function($to, $model) {
//                    $model->total = 100;
//                },
//                function($to, $model) {
//                    $model->notes = 'Notes updated';
//                },
//                function ($to, $model) {
//                }
//            ]
//        ];
//    }
}
