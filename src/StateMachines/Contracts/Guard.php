<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Contracts;

use byteit\LaravelExtendedStateMachines\StateMachines\Transition;

interface Guard
{

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Transition  $transition
     *
     * @return bool
     *
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException
     */
    public function guard(Transition $transition): bool;
}
