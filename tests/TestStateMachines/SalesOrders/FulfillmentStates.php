<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;


use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasGuards;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards\PartialGuard;


#[HasGuards([PartialGuard::class])]
enum FulfillmentStates: string implements States
{

    case Pending = 'pending';

    case Partial = 'partial';

    case Complete = 'complete';

    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Complete, self::Partial],
            self::Partial => [self::Complete],
            default => []
        };
    }

}
