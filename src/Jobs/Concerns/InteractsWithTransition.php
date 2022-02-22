<?php

namespace byteit\LaravelExtendedStateMachines\Jobs\Concerns;

use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;

trait InteractsWithTransition
{
    public PendingTransition $transition;

    public function setTransition(PendingTransition $transition): self{
        $this->transition = $transition;

        return $this;
    }
}
