<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

#[\Attribute]
class DefaultState
{
    public function __construct(public States $default)
    {
    }

}
