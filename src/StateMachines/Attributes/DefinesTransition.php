<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use InvalidArgumentException;
use TypeError;

abstract class DefinesTransition
{
    public function __construct(
      public readonly ?States $from = null,
      public readonly ?States $to = null,
    ) {
        if ($this->from === null && $this->to === null) {
            throw new InvalidArgumentException('Either $from or $to must be set');
        }

        if ($this->to && $this->from && $this->to::class !== $this->from::class) {
            throw new TypeError(sprintf('%s: $to %s and $form %s must be of same type', $this->to::class, $this->to::class, $this->from::class));
        }

    }
}
