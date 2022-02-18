<?php


namespace byteit\LaravelExtendedStateMachines\StateMachines;


use byteit\LaravelExtendedStateMachines\Events\TransitionCompleted;
use byteit\LaravelExtendedStateMachines\Events\TransitionStarted;
use byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use byteit\LaravelExtendedStateMachines\Models\Transition as TransitionModel;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\After;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasGuards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Traits\HasStateMachines;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @todo merge with customizations from kasching
 */
class StateMachine
{

    public static array $booted = [];

    /**
     * @var string The field on the model
     */
    public string $field;

    /**
     * @var string|States The States implementation
     */
    public string|States $states;

    /**
     * @var \Illuminate\Database\Eloquent\Model&HasStateMachines The model to
     *   act on
     */
    public Model $model;

    /**
     * @param  string  $field  The model field
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $states  The States enum class
     *
     * @throws \ReflectionException|\Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct(string $field, Model $model, string $states)
    {
        $this->field = $field;

        $this->model = $model;

        $this->states = $states;


        if ( ! isset(self::$booted[$states])) {
            self::boot($states);
        }
    }

    /**
     * @throws \ReflectionException|\Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function boot(string $states): void
    {
        $reflection = new ReflectionEnum($states);

        /** @var HasActions $actions */
        $actions = Arr::first($reflection->getAttributes(HasActions::class))
          ?->newInstance();

        if ($actions) {
            /**
             * Collect all registered actions, then scan all methods for the
             * `Before` and `After` attributes and instantiate them.
             * The result is a `Collection` keyed by the class containing arrays
             * in the format `methodName` => [...AttributeInstances]
             */
            collect($actions->actions)
              ->each(function (string $class) use ($states) {
                  $reflection = new ReflectionClass($class);

                  collect($reflection->getMethods())
                    ->mapWithKeys(fn(ReflectionMethod $method) => [
                      $method->name => array_merge(
                        $method->getAttributes(Before::class),
                        $method->getAttributes(After::class)
                      ),
                    ])
                    ->filter(fn(array $attributes) => count($attributes) > 0)
                    ->map(fn(array $attributes) => Arr::first($attributes)
                      ->newInstance())
                    /**
                     * Now we need to register event listeners for all aggregated handlers.
                     * In the first step, generate the event name, respecting eventual wildcards.
                     */
                    ->each(function (
                      After|Before $attribute,
                      string $method
                    ) use (
                      $states,
                      $class,
                      $reflection,
                    ) {

                        /*
                         * First try to get the model class from the method parameters.
                         * If it has none
                         */
                        $methodReflection = $reflection->getMethod($method);
                        $parameters = Arr::first($methodReflection->getParameters());
                        $type = $parameters->getType();

                        $model = '*';

                        if ($type !== null) {
                            if ($type instanceof ReflectionNamedType) {
                                if ( ! $type->isBuiltin() && ! ($type->getName() === Model::class)) {
                                    $model = $type->getName();
                                }
                            } else {
                                throw new InvalidArgumentException("Type unions or intersections aren't allowed.");
                            }
                        }

                        $eventName = self::event(
                          $attribute->from,
                          $attribute->to,
                          $model,
                          before: $attribute instanceof Before,
                          after: $attribute instanceof After,
                        );

                        Event::listen($eventName,
                          static function ($eventName, array $data) use (
                            $class,
                            $method
                          ) {
                              /** @var TransitionStarted|TransitionCompleted $event */
                              $event = Arr::first($data);
                              return app()
                                ->make($class)
                                ->{$method}($event->transition->model, $event);
                          });
                    });
              });


        }

        self::$booted[$states] = true;
    }

    /**
     * @return mixed
     */
    public function currentState(): States
    {
        $field = $this->field;

        return $this->model->$field;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function history(): MorphMany
    {
        return $this->model->stateHistory()->forField($this->field);
    }

    /**
     * @param  States  $state
     *
     * @return bool
     */
    public function was(
      States $state
    ): bool {
        return $this->history()->to($state)->exists();
    }

    /**
     * @param  States  $state
     *
     * @return mixed
     */
    public function timesWas(
      States $state
    ): int {
        return $this->history()->to($state)->count();
    }

    /**
     * @param  States  $state
     *
     * @return \Carbon\Carbon|null
     */
    public function whenWas(
      States $state
    ): ?Carbon {
        $stateHistory = $this->snapshotWhen($state);

        if ($stateHistory === null) {
            return null;
        }

        return $stateHistory->created_at;
    }

    /**
     * @param  States  $state
     *
     * @return TransitionModel|null
     */
    public function snapshotWhen(
      States $state
    ): ?TransitionModel {
        return $this->history()->to($state)->latest('id')->first();
    }

    /**
     * @param  States  $state
     *
     * @return \Illuminate\Support\Collection
     */
    public function snapshotsWhen(
      States $state
    ): Collection {
        return $this->history()->to($state)->get();
    }

    /**
     * @param  States  $from
     * @param  States  $to
     *
     * @return bool
     */
    public function canBe(
      States $from,
      States $to
    ): bool {
        return in_array($to, $from->transitions(), true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function postponedTransitions(): MorphMany
    {
        return $this->model->postponedTransitions()->forField($this->field);
    }

    /**
     * @return bool
     */
    public function hasPostponedTransitions(): bool
    {
        return $this->postponedTransitions()->notApplied()->exists();
    }

    /**
     * @param  States  $from
     * @param  States  $to
     * @param  array  $customProperties
     * @param  mixed|null  $responsible
     *
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException
     * @throws \ReflectionException
     */
    public function transitionTo(
      States $from,
      States $to,
      array $customProperties = [],
      mixed $responsible = null
    ): void {
        if ($to === $this->currentState()) {
            return;
        }

        if ( ! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException("Transition from [$from->value] to [$to->value] on [$this->states] is illegal");
        }

        $reflection = new ReflectionEnum($this->states);

        $transition = new Transition(
          $this,
          $from,
          $to,
          $this->model,
          $customProperties,
          $responsible
        );


        $guardsResult = $this->resolveGuards($to, $from, $reflection)
          ->map(function (Closure $guard) use ($transition) {
              try {
                  return $guard($transition, $this);
              } catch (Exception $exception) {
                  return $exception;
              }
          })
          ->reject(fn(mixed $result) => $result === true);

        if ($guardsResult->isNotEmpty()) {
            throw new TransitionGuardException("A guard canceled the transition from {$from->value} to {$to->value}");
        }

        event(
          self::event($from, $to, $this->model::class, before: true),
          new TransitionStarted($transition)
        );

        $field = $this->field;

        $this->model->$field = $to;

        $changedAttributes = $this->model->getChangedAttributes();

        $this->model->save();

        if ($this->recordHistory()) {
            $responsible = $responsible ?? auth()->user();

            $this->model->recordState(
              $field,
              $from,
              $to,
              $customProperties,
              $responsible,
              $changedAttributes
            );
        }

        event(
          self::event($from, $to, $this->model::class, after: true),
          new TransitionCompleted($transition)
        );

        // @todo allow keeping
        $this->cancelAllPostponedTransitions();
    }

    /**
     * @param $from
     * @param $to
     * @param  Carbon  $when
     * @param  array  $customProperties
     * @param  null  $responsible
     *
     * @return null|PostponedTransition
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
      $from,
      $to,
      Carbon $when,
      array $customProperties = [],
      $responsible = null
    ): ?PostponedTransition {


        if ( ! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException();
        }

        $responsible = $responsible ?? auth()->user();

        return $this->model->recordPostponedTransition(
          $this->field,
          $from,
          $to,
          $when,
          $customProperties,
          $responsible
        );
    }

    /**
     * @return void
     */
    public function cancelAllPostponedTransitions(): void
    {
        $this->postponedTransitions()->delete();
    }

    /**
     * @return array
     */
    public function transitions(): array
    {
        return collect($this->states::cases())
          ->map(fn(States $states) => $states->transitions())
          ->all();
    }

    /**
     * @return States
     */
    public function defaultState(): States
    {
        try {
            $reflection = new ReflectionClass($this->states);
            $attributes = $reflection->getAttributes(DefaultState::class);
        } catch (ReflectionException) {
            $attributes = [];
        }

        return match (count($attributes)) {
            0 => Arr::first($this->states::cases()),
            1 => Arr::first($attributes)->newInstance()->default,
        };
    }

    /**
     * @return bool
     */
    public function recordHistory(): bool
    {
        try {
            $reflection = new ReflectionClass($this->states);
            $attributes = $reflection->getAttributes(RecordHistory::class);

        } catch (ReflectionException) {
            return false;
        }
        /** @var DefaultState[] $attributes */
        return count($attributes) === 1;
    }

    protected function resolveGuards(
      States $to,
      States $from,
      ReflectionEnum $reflection
    ): Collection {
        $classGuards = collect($reflection->getAttributes(HasGuards::class))
          ->map(fn(ReflectionAttribute $attribute) => $attribute->newInstance())
          ->map(fn(HasGuards $instance) => $instance->guards)
          ->flatten()
          ->map(fn(string $class) => static function (Transition $transition
          ) use ($class) {
              return App::make($class)->guard($transition);
          });

        $inlineGuards = collect($reflection->getMethods())
          ->mapWithKeys(fn(ReflectionMethod $method
          ) => [$method->name => $method])
          ->map(fn(ReflectionMethod $method
          ) => Arr::first($method->getAttributes(Guards::class))
            ?->newInstance())
          ->reject(null)
          ->filter(fn(Guards $guard
          ) => $guard->from === null || $guard->from === $from)
          ->filter(fn(Guards $guard
          ) => ($guard->from !== null && $guard->to === null) || $guard->to === $to)
          ->map(fn(Guards $guards, string $method) => static function (
            Transition $transition,
            StateMachine $machine
          ) use ($method) {
              return $machine->states::{$method}($transition);
          });


        return $classGuards->merge($inlineGuards);
    }

    /**
     * Generates the event name, including wildcards
     *
     * @param  States|null  $from
     * @param  States|null  $to
     * @param  string|null  $model
     * @param  bool  $before
     * @param  bool  $after
     *
     * @return string
     */
    public static function event(
      ?States $from = null,
      ?States $to = null,
      ?string $model = null,
      bool $before = false,
      bool $after = false
    ): string {

        $states = match (true) {
            $from !== null => $from::class,
            $to !== null => $to::class,
            default => throw new InvalidArgumentException('At least one of $to or $form must be not null')
        };

        return collect([
          $states,
          $model ?? '*',
          $attribute->from->value ?? '*',
          $attribute->to->value ?? '*',
          match (true) {
              $before && $after => '*',
              $before => 'before',
              $after => 'after',
              default => throw new InvalidArgumentException('At least oe of $before or $after must be true')
          },
        ])->join('.');
    }

}
