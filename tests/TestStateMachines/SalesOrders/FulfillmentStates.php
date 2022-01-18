<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;


use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\StateMachines\StateMachine;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\StartSalesOrderFulfillmentJob;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;

enum FulfillmentStates: string implements States
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Complete = 'complete';

    public function transition(): array
    {
        return match ($this) {
            self::Pending => [self::Complete, self::Partial],
            self::Partial => [self::Complete],
            default => []
        };
    }

//    public function validatorForTransition($from, $to, $model): ?Validator
//    {
//        if ($from === null && $to === 'pending') {
//            return ValidatorFacade::make([
//                'status' => $model->status,
//            ], [
//                'status' => Rule::in('approved'),
//            ]);
//        }
//
//        return parent::validatorForTransition($from, $to, $model);
//    }
//
//    public function afterTransitionHooks(): array
//    {
//        return [
//            'pending' => [
//                function ($from, $model) {
//                    StartSalesOrderFulfillmentJob::dispatch($model);
//                },
//                function ($from, $model) {
//                    // Do something else
//                },
//            ],
//        ];
//    }
}
