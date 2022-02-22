<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;


use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\AfterTransitionJob;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionSubscriber\AddNotes;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionSubscriber\DispatchJob;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionSubscriber\UpdateOrderTotal;

#[RecordHistory]
#[DefaultState(StatusWithAfterTransitionHookStates::Pending)]
#[HasActions([AddNotes::class, DispatchJob::class, UpdateOrderTotal::class])]
enum StatusWithAfterTransitionHookStates :string implements States
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


//    public function afterTransitionHooks(): array
//    {
//        return [
//            'approved' => [
//                function($from, $model) {
//                    $model->total = 200;
//                    $model->save();
//                },
//                function($from, $model) {
//                    $model->notes = 'after';
//                    $model->save();
//                },
//                function ($from, $model) {
//                    AfterTransitionJob::dispatch();
//                },
//            ]
//        ];
//    }
}
