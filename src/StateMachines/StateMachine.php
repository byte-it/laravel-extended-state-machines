<?php


namespace byteit\LaravelExtendedStateMachines\StateMachines;


use byteit\LaravelExtendedStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use byteit\LaravelExtendedStateMachines\Models\Transition;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefinesTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasGuards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionMethod;

/**
 * @todo merge with customizations from kasching
 */
class StateMachine
{

    public static array $booted = [];

    /**
     * @var string|States The States implementation
     */
    public string|States $states;

    protected ReflectionEnum $reflection;

    /**
     * @param  string  $states  The States enum class
     *
     * @throws \ReflectionException
     */
    public function __construct(string $states)
    {

        $this->states = $states;

        $this->reflection = new ReflectionEnum($states);

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
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
     */
    public function assertCanBe(
        States $from,
        States $to
    ): void {
        if ( ! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException("Transition from [$from->value] to [$to->value] on [$this->states] is illegal");
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $field
     * @param  States  $from
     * @param  States  $to
     * @param  array  $customProperties
     * @param  mixed|null  $responsible
     *
     * @return \byteit\LaravelExtendedStateMachines\Contracts\Transition|null
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
     */
    public function transitionTo(
        Model $model,
        string $field,
        States $from,
        States $to,
        array $customProperties = [],
        mixed $responsible = null
    ): ?TransitionContract {

        $this->assertCanBe($from, $to);


        $transition = $this->makeTransition(
            $model,
            $field,
            $from,
            $to,
            $customProperties,
            $responsible
        );


        $transition = $transition->dispatch();

        if ($transition instanceof PendingTransition && $transition->pending()) {
            // @todo: Record pending stuff

            return $transition;
        }


        if ($transition instanceof Transition || $transition instanceof PendingTransition) {
            $transition->save();
            return $transition;
        }

        return null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model&\byteit\LaravelExtendedStateMachines\Traits\HasStateMachines  $model
     * @param  string  $field
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $from
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $to
     * @param  Carbon  $when
     * @param  array  $customProperties
     * @param  null  $responsible
     *
     * @return null|PostponedTransition
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
     */
    public function postponeTransitionTo(
        Model $model,
        string $field,
        States $from,
        States $to,
        Carbon $when,
        array $customProperties = [],
        $responsible = null
    ): ?PostponedTransition {


        $this->assertCanBe($from, $to);

        $transition = $this
            ->makeTransition($model,$field, $from, $to, $customProperties, $responsible)
            ->postpone($when)
            ->toTransition();

        if ($transition instanceof PostponedTransition) {
            $transition->save();

            return $transition;
        }

        return null;
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

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $to
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $from
     *
     * @return array
     */
    public function resolveGuards(States $to, States $from): array
    {
        return $this->resolveAttributes($to, $from, guards: true);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $from
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $to
     *
     * @return string|null
     */
    public function resolveAction(States $from, States $to): ?string
    {
        return Arr::first($this->resolveAttributes($to, $from, actions: true));
    }

    protected function resolveAttributes(
        States $to,
        States $from,
        bool $guards = false,
        bool $actions = false,
    ): array {
        if ($guards && $actions) {
            throw new InvalidArgumentException("Only guards or actions");
        }

        $hasAttribute = $guards ? HasGuards::class : HasActions::class;
        $transitionAttribute = $guards ? Guards::class : Before::class;

        $classes = collect($this->reflection->getAttributes($hasAttribute))
            ->map(fn(ReflectionAttribute $attribute
            ) => $attribute->newInstance())
            ->map(fn(HasGuards|HasActions $instance) => $instance->classes)
            ->flatten()
            ->mapWithKeys(function (string $class) use ($transitionAttribute) {
                $reflection = new ReflectionClass($class);
                /** @var DefinesTransition $instance */
                $instance = Arr::first($reflection->getAttributes($transitionAttribute))
                    ?->newInstance();

                return [$class => $instance];
            })
            ->reject(null);

        // @todo Filter by transition

        $inline = collect($this->reflection->getMethods())
            ->mapWithKeys(fn(ReflectionMethod $method
            ) => [$method->name => $method])
            ->map(fn(ReflectionMethod $method
            ) => Arr::first($method->getAttributes($transitionAttribute))
                ?->newInstance())
            ->reject(null)
            ->reject(fn(DefinesTransition $instance
            ) => $instance->from !== null && $instance->from !== $from)
            ->reject(fn(DefinesTransition $instance
            ) => $instance->to !== null && $instance->to !== $to);


        return $classes->merge($inline)
            ->reject(fn(DefinesTransition $instance
            ) => $instance->from !== null && $instance->from !== $from)
            ->reject(fn(DefinesTransition $instance
            ) => $instance->to !== null && $instance->to !== $to)
            ->keys()
            ->all();
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

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $field
     * @param  States  $from
     * @param  States  $to
     * @param  mixed  $customProperties
     * @param  mixed  $responsible
     *
     * @return \byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition
     */
    protected function makeTransition(
        Model $model,
        string $field,
        States $from,
        States $to,
        mixed $customProperties,
        mixed $responsible = null
    ): PendingTransition {

        $responsible = $responsible ?? auth()->user();

        return new PendingTransition(
            $this,
            $from,
            $to,
            $model,
            $field,
            $customProperties,
            $responsible
        );
    }


    public function __sleep(): array
    {
        return [
            'states',
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup(): void
    {
        $this->reflection = new ReflectionEnum($this->states);
    }

}
