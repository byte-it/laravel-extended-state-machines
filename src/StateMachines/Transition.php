<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines;


use ArrayAccess;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\Guard;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class Transition
{

    protected array $guards = [];
    /**
     * @param  StateMachine  $stateMachine
     * @param  States|null  $from
     * @param  States  $to
     * @param  Model  $model
     * @param  array|Arrayable|ArrayAccess  $customProperties
     * @param  mixed  $responsible
     */
    public function __construct(
      public readonly StateMachine $stateMachine,
      public readonly States|null $from,
      public readonly States $to,
      public readonly Model $model,
      public array|Arrayable|ArrayAccess $customProperties,
      public readonly mixed $responsible
    ) {
    }

    /**
     * @return Guard[]
     */
    public function guards(): array
    {
        return collect($this->guards)
          ->map(fn(string $class) => app()->make($class))
          ->all();
    }



    public function customProperties(): array
    {
        return $this->customProperties;
    }


}
