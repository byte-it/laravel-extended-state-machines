<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines;

use byteit\LaravelExtendedStateMachines\Events\TransitionCompleted;
use byteit\LaravelExtendedStateMachines\Events\TransitionStarted;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\After;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionNamedType;

class TransitionActionInterceptor
{

    public function __construct(protected Application $application)
    {
    }

    /**
     * @throws \ReflectionException
     */
    public function intercept(
      TransitionStarted|TransitionCompleted $event,
      string $class,
      string $method
    ): mixed {

        $modelClass = $event->transition->model::class;

        $instance = $this->application->make($class);

        $reflection = new ReflectionClass($class);

        $reflectionMethod = $reflection->getMethod($method);

        $attribute = Arr::first($reflectionMethod->getAttributes(match (true){
            $event instanceof TransitionStarted => Before::class,
            $event instanceof TransitionCompleted => After::class,
        }));

        if($attribute === null){
            return null;
        }

        $parameters = $reflectionMethod->getParameters();

        $desired = false;
        foreach ($parameters as $parameter){
            $type = $parameter->getType();

            if($type instanceof ReflectionNamedType && $type->getName() === $modelClass){
                $desired = true;
            }
        }

        return;
    }

}
