<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Contracts;

use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;

interface Guard
{

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition  $transition
     *
     * @return bool
     *
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException
     */
    public function guard(PendingTransition $transition): bool;
}
