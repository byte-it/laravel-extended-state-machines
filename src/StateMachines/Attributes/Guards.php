<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Guards extends DefinesTransition
{
}
