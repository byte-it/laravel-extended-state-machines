<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasGuards;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards\FalseGuard;

#[HasGuards([FalseGuard::class])]
enum State: string implements States
{

    case Init = 'init';
    case Intermediate = 'intermediate';
    case Guarded = 'guarded';
    case InlineGuarded = 'inline_guarded';
    case Finished = 'finished';

    public function transitions(): array
    {
        return match ($this){
            self::Init => [self::Intermediate, self::Guarded, self::InlineGuarded],
            self::Intermediate => [self::Finished],
            default => []
        };
    }

    #[Guards(to: self::InlineGuarded)]
    public function inlineGuard(PendingTransition $transition): bool{
        return false;
    }

}
