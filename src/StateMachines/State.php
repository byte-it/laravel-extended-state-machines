<?php


namespace byteit\LaravelExtendedStateMachines\StateMachines;

use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\Models\PendingTransition;
use byteit\LaravelExtendedStateMachines\Models\StateHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Class State
 * @package byteit\LaravelExtendedStateMachines\StateMachines
 * @property States $state
 * @property StateMachine $stateMachine
 */
class State
{

    /**
     * @var \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States
     */
    public States $state;

    /**
     * @var \byteit\LaravelExtendedStateMachines\StateMachines\StateMachine
     */
    public StateMachine $stateMachine;

    public function __construct(States $state, StateMachine $stateMachine)
    {
        $this->state = $state;
        $this->stateMachine = $stateMachine;
    }

    /**
     * @return \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States
     */
    public function state(): States
    {
        return $this->state;
    }

    /**
     * @return \byteit\LaravelExtendedStateMachines\StateMachines\StateMachine
     */
    public function stateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function is(States $state): bool
    {
        return $this->state === $state;
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function isNot(States $state): bool
    {
        return !$this->is($state);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function was(States $state): bool
    {
        return $this->stateMachine->was($state);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return mixed
     */
    public function timesWas(States $state): int
    {
        return $this->stateMachine->timesWas($state);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return \Carbon\Carbon|null
     */
    public function whenWas(States $state): ?Carbon
    {
        return $this->stateMachine->whenWas($state);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return \byteit\LaravelExtendedStateMachines\Models\StateHistory|null
     */
    public function snapshotWhen(States $state): ?StateHistory
    {
        return $this->stateMachine->snapshotWhen($state);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return \Illuminate\Support\Collection
     */
    public function snapshotsWhen(States $state): Collection
    {
        return $this->stateMachine->snapshotsWhen($state);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function history(): MorphMany
    {
        return $this->stateMachine->history();
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function canBe(States $state): bool
    {
        return $this->stateMachine->canBe($from = $this->state, $to = $state);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function pendingTransitions(): MorphMany
    {
        return $this->stateMachine->pendingTransitions();
    }

    /**
     * @return bool
     */
    public function hasPendingTransitions(): bool
    {
        return $this->stateMachine->hasPendingTransitions();
    }

    /**
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
     */
    public function transitionTo(States $to, $customProperties = [], $responsible = null): void
    {
        $this->stateMachine->transitionTo(
            $this->state,
            $to,
            $customProperties,
            $responsible
        );
    }

    /**
     * @param States $state
     * @param Carbon $when
     * @param  array  $customProperties
     * @param null $responsible
     *
     * @return null|PendingTransition
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(States $state, Carbon $when, array $customProperties = [], $responsible = null) : ?PendingTransition
    {
        return $this->stateMachine->postponeTransitionTo(
            $this->state,
            $state,
            $when,
            $customProperties,
            $responsible
        );
    }

    /**
     * @return \byteit\LaravelExtendedStateMachines\Models\StateHistory|null
     */
    public function latest() : ?StateHistory
    {
        return $this->snapshotWhen($this->state);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getCustomProperty(string $key): mixed
    {
        return optional($this->latest())->getCustomProperty($key);
    }

    /**
     * @return mixed
     */
    public function responsible(): mixed
    {
        return optional($this->latest())->responsible;
    }

    /**
     * @return array
     */
    public function allCustomProperties(): array
    {
        return optional($this->latest())->allCustomProperties() ?? [];
    }
}
