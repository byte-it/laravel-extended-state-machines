<?php

namespace byteit\LaravelExtendedStateMachines\Jobs;

use byteit\LaravelExtendedStateMachines\Jobs\Concerns\InteractsWithTransition;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TransitionAction
{

    use InteractsWithQueue, InteractsWithTransition, Queueable, Dispatchable, SerializesModels;

    public function __construct(
      protected mixed $action
    ) {

    }

    public function handle(Application $app): void
    {
        $method = method_exists(
          $this->action,
          'handle'
        ) ? 'handle' : '__invoke';

        if($this->job){
            $this->setJobInstanceIfNecessary($this->action);
        }

        $this->setTransitionInstanceIfNecessary($this->action);

        $response = $this->action->{$method}($this->transition->model);

        if ($response instanceof PendingChain) {
            $transition = $this->transition;
            $response->chain[] = static function () use ($transition) {
                $transition->finishAction();
            };
            $response->dispatch();
            return;
        }

        if ($response instanceof PendingBatch) {
            $transition = $this->transition;
            $response->then(static function () use ($transition) {
                $transition->finishAction();
            });

            $response->dispatch();
            return;
        }

        $this->transition->finishAction();
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  mixed  $instance
     *
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(mixed $instance): mixed
    {
        if (in_array(
          InteractsWithQueue::class,
          class_uses_recursive($instance::class),
          true)
        ) {
            $instance->setJob($this->job);
        }

        return $instance;
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  mixed  $instance
     *
     * @return mixed
     */
    protected function setTransitionInstanceIfNecessary(mixed $instance): mixed
    {
        if (in_array(InteractsWithTransition::class,
          class_uses_recursive($instance::class),
          true)
        ) {
            $instance->setTransition($this->transition);
        }

        return $instance;
    }

    /**
     * Extract the queue connection for the action.
     *
     * @param $action
     *
     * @return string|null
     */
    public static function connection($action): ?string
    {
        return property_exists($action, 'connection') ? $action->connection : null;
    }

    /**
     * Extract the queue name for the action.
     *
     * @param $action
     *
     * @return string|null
     */
    public static function queue($action): ?string
    {
        return property_exists($action, 'queue') ? $action->queue : null;
    }
}
