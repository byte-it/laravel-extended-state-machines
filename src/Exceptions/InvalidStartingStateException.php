<?php


namespace byteit\LaravelExtendedStateMachines\Exceptions;


use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Exception;

class InvalidStartingStateException extends Exception
{
    public function __construct(States $expectedState, States $actualState)
    {
        $message = "Expected: $expectedState->value. Actual: $actualState->value";

        parent::__construct($message);
    }
}
