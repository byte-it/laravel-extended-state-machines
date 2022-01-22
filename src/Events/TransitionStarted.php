<?php

namespace byteit\LaravelExtendedStateMachines\Events;

use byteit\LaravelExtendedStateMachines\StateMachines\Transition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransitionStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(
      public readonly Transition $transition
    )
    {
    }

}
