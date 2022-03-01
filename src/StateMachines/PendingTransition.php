<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines;


use ArrayAccess;
use byteit\LaravelExtendedStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelExtendedStateMachines\Events\TransitionCompleted;
use byteit\LaravelExtendedStateMachines\Events\TransitionStarted;
use byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelExtendedStateMachines\Jobs\TransitionAction;
use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use byteit\LaravelExtendedStateMachines\Models\Transition;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

/**
 *
 */
class PendingTransition implements TransitionContract
{
    use SerializesModels;

    protected array $guards = [];

    protected bool $pending = true;

    protected bool $canceled = true;

    protected ?Carbon $postponedTo = null;

    protected mixed $action = null;

    protected array $changes = [];

    /**
     * @param  StateMachine  $stateMachine
     * @param  States|null  $from
     * @param  States  $to
     * @param  Model&\byteit\LaravelExtendedStateMachines\Traits\HasStateMachines  $model
     * @param  string  $field
     * @param  array|Arrayable|ArrayAccess  $customProperties
     * @param  mixed  $responsible
     */
    public function __construct(
      public readonly StateMachine $stateMachine,
      public readonly States|null $from,
      public readonly States $to,
      public readonly Model $model,
      public readonly string $field,
      public array|Arrayable|ArrayAccess $customProperties,
      public readonly mixed $responsible
    ) {
    }

    /**
     * @param  \Carbon\Carbon  $when
     *
     * @return $this
     */
    public function postpone(Carbon $when): self
    {
        $this->postponedTo = $when;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldPostpone(): bool
    {
        return $this->postponedTo !== null;
    }

    public function customProperties(): array
    {
        return $this->customProperties;
    }

    public function pending(): bool
    {
        return $this->pending;
    }

    /**
     * @return \byteit\LaravelExtendedStateMachines\Contracts\Transition
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException
     */
    public function dispatch(): TransitionContract
    {

        if ( ! $this->checkGates()) {
            throw new AuthorizationException();
        }

        if ( ! $this->checkGuards()) {
            throw new TransitionGuardException("A guard canceled the transition from [{$this->from->value}] to [{$this->to->value}]");
        }

        event(
          StateMachine::event($this->from, $this->to, $this->model::class,
            before: true),
          new TransitionStarted($this)
        );

        $this->changes = $this->model->getChangedAttributes();

        $this->dispatchAction();

        if ( ! $this->pending) {

        }

        return $this->toTransition();

    }

    /**
     * Check the gates if the current user is authorized to perform the
     * transition.
     *
     * @return bool
     */
    protected function checkGates(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function checkGuards(): bool
    {
        return collect($this->stateMachine->resolveGuards(
          $this->to,
          $this->from
        ))
          ->map(function (string $guard) {
              if (method_exists($this->to, $guard)) {
                  $to = $this->to;
                  return static function (PendingTransition $transition) use (
                    $to,
                    $guard
                  ) {
                      return $to->{$guard}($transition);
                  };
              }

              return static function (PendingTransition $transition) use ($guard
              ) {
                  /** @var \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\Guard $instance */
                  $instance = app()->make($guard);
                  return $instance->guard($transition);
              };
          })
          ->map(function (Closure $guard) {
              try {
                  return $guard($this);
              } catch (Exception $exception) {
                  return $exception;
              }
          })
          ->reject(fn(mixed $result) => $result === true)
          ->isEmpty();
    }

    /**
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function dispatchAction(): void
    {
        $action = $this->stateMachine->resolveAction($this->from, $this->to);
        if ($action === null) {
            $this->finishAction();
            return;
        }

        if (method_exists($this->to, $action)) {
            $actionInstance = function ($model) use ($action){
                call_user_func([$this->to, $action], $model);
            };

        }
        else{
            $actionInstance = app()->make($action);
        }



        $job = (new TransitionAction($actionInstance))->setTransition($this);

        if($actionInstance instanceof ShouldQueue){
            $queue = TransitionAction::queue($actionInstance);
            $connection = TransitionAction::connection($actionInstance);

            Queue::connection($connection)->pushOn($queue, $job);
        }
        else {
            app()->call([$job, 'handle']);
        }


    }

    public function finishAction(): void
    {

        $this->model->{$this->field} = $this->to;

        $this->changes = array_merge(
          $this->changes,
          $this->model->getChangedAttributes()
        );
        $this->pending = false;


        $this->model->save();

        event(
          StateMachine::event($this->from, $this->to, $this->model::class,
            after: true),
          new TransitionCompleted($this)
        );
    }

    /**
     * @return TransitionContract
     */
    public function toTransition(): TransitionContract
    {
        $properties = [
          'field' => $this->field,
          'from' => $this->from,
          'to' => $this->to,
          'states' => $this->to::class,
          'custom_properties' => $this->customProperties,
        ];

        if ($this->pending && $this->postponedTo) {
            $postponedTransition = new PostponedTransition([
              ...$properties,
              'transition_at' => $this->postponedTo,
            ]);

            if ($this->responsible !== null) {
                $postponedTransition->responsible()
                  ->associate($this->responsible);
            }

            $postponedTransition->model()->associate($this->model);
            return $postponedTransition;
        }

        if ($this->pending) {
            return $this;
        }

        $transition = new Transition([
          ...$properties,
          'changed_attributes' => $this->changes,
        ]);

        $transition->model()->associate($this->model);
        if ($this->responsible !== null) {
            $transition->responsible()->associate($this->responsible);
        }

        return $transition;
    }

}
