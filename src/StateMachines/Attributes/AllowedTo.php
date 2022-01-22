<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

#[Attribute]
class AllowedTo
{

    /**
     * @param  States[]  $allowed
     */
    public function __construct(public array $allowed)
    {
    }

}
