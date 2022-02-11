<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Attributes;

use Attribute;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use InvalidArgumentException;
use TypeError;

#[Attribute(Attribute::TARGET_METHOD)]
class Before
{

    public function __construct(
      public readonly string $states,
      public readonly ?States $from = null,
      public readonly ?States $to = null,
    ) {
        if ($this->from === null && $this->to === null) {
            throw new InvalidArgumentException('Either $from or $to must be set');
        }

        if ($this->to && $this->to::class !== $this->states) {
            throw new TypeError(sprintf('$to needs to of type %s instead %s was given',
              $this->states, $this->to::class));
        }

        if ($this->from && $this->from::class !== $this->states) {
            throw new TypeError(sprintf('$from needs to of type %s instead %s was given',
              $this->states, $this->to::class));
        }
    }

}
