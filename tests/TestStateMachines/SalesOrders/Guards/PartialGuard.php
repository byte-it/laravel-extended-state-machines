<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards;

use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\Guard;
use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartialGuard implements Guard
{

    /**
     * @inheritDoc
     */
    public function guard(PendingTransition $transition): bool
    {
        return  Validator::make([
                'status' => $transition->model->status->value,
            ], [
                'status' => Rule::in(StatusStates::Approved->value),
            ])->validate();
    }

}
