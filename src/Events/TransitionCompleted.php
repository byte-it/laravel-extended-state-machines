<?php

namespace byteit\LaravelExtendedStateMachines\Events;

use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransitionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(
      public readonly PendingTransition $transition
    )
    {
    }
}
