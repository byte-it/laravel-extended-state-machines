<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;

#[Attribute]
class HasGuards
{
    public function __construct(
      public readonly array $guards
    )
    {
    }

}
