<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use InvalidArgumentException;
use TypeError;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD)]
class Before extends DefinesTransition
{
}
