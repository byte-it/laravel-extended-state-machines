<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;

#[Attribute]
class HasActions
{
    public function __construct(
      public readonly array $actions
    )
    {
    }

}
