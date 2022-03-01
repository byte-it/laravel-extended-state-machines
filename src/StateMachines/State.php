<?php


namespace byteit\LaravelExtendedStateMachines\StateMachines;

use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use byteit\LaravelExtendedStateMachines\Models\Transition;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use TypeError;

/**
 * Represents the current state for the field and state machine
 *
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

    /** @var \Illuminate\Database\Eloquent\Model&\byteit\LaravelExtendedStateMachines\Traits\HasStateMachines  */
    protected Model $model;

    /**
     * @var string
     */
    protected string $field;

    public function __construct(States $state, Model $model, string $field,StateMachine $stateMachine)
    {
        $this->state = $state;
        $this->model = $model;
        $this->field = $field;
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
        $this->assertStateClass($state);
        return $this->state === $state;
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function isNot(States $state): bool
    {
        $this->assertStateClass($state);
        return !$this->is($state);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function was(States $state): bool
    {
        $this->assertStateClass($state);
        return $this->history()->to($state)->exists();
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return mixed
     */
    public function timesWas(States $state): int
    {
        $this->assertStateClass($state);
        return $this->history()->to($state)->count();
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return \Carbon\Carbon|null
     */
    public function whenWas(States $state): ?Carbon
    {
        $this->assertStateClass($state);
        $stateHistory = $this->snapshotWhen($state);

        if ($stateHistory === null) {
            return null;
        }

        return $stateHistory->created_at;
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return \byteit\LaravelExtendedStateMachines\Models\Transition|null
     */
    public function snapshotWhen(States $state): ?Transition
    {
        $this->assertStateClass($state);
        return $this->history()->to($state)->latest('id')->first();
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return \Illuminate\Support\Collection
     */
    public function snapshotsWhen(States $state): Collection
    {
        $this->assertStateClass($state);
        return $this->history()->to($state)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function history(): MorphMany
    {
        return $this->model->stateHistory()->forField($this->field);
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $state
     *
     * @return bool
     */
    public function canBe(States $state): bool
    {
        $this->assertStateClass($state);
        return $this->stateMachine->canBe($this->state, $state);
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
        return collect($this->state::cases())
            ->map(fn(States $states) => $states->transitions())
            ->all();
    }

    /**
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $to
     * @param  array  $customProperties
     * @param  null  $responsible
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionGuardException
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
     */
    public function transitionTo(States $to, array $customProperties = [], $responsible = null): void
    {
        $this->stateMachine->transitionTo(
            $this->model,
            $this->field,
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
     * @return null|PostponedTransition
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(States $state, Carbon $when, array $customProperties = [], $responsible = null) : ?PostponedTransition
    {
        return $this->stateMachine->postponeTransitionTo(
            $this->model,
            $this->field,
            $this->state,
            $state,
            $when,
            $customProperties,
            $responsible
        );
    }

    /**
     * @return \byteit\LaravelExtendedStateMachines\Models\Transition|null
     */
    public function latest() : ?Transition
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

    protected function assertStateClass(States $state): void{
        if(!($state instanceof $this->state)){
            throw new TypeError(sprintf('$state must be of type %s, instead %s  was given.', $this->state::class, $state::class));
        }
    }
}
