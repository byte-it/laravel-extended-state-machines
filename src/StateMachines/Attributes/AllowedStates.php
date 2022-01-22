<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;

#[Attribute]
class AllowedStates
{

    /**
     * @param  string  $states The States Enum
     */
    public function __construct(public string $states)
    {
    }

}
