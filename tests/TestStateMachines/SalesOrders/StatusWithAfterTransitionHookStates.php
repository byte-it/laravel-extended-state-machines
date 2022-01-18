<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;


use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\AfterTransitionJob;

#[RecordHistory]
#[DefaultState(StatusWithAfterTransitionHookStates::Pending)]
enum StatusWithAfterTransitionHookStates :string implements States
{

    case Pending = 'pending';
    case Approved =  'approved';
    case Processed = 'processed';

    public function transition(): array
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
