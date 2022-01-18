<?php


namespace byteit\LaravelExtendedStateMachines\StateMachines;


use byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelExtendedStateMachines\Models\PendingTransition;
use byteit\LaravelExtendedStateMachines\Models\StateHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionException;

/**
 * @todo merge with customizations from kasching
 */
class StateMachine
{

    /**
     * @var string The field on the model
     */
    public string $field;

    /**
     * @var string|States The States implementation
     */
    public string|States $states;

    /**
     * @var \Illuminate\Database\Eloquent\Model The model to act on
     */
    public Model $model;

    /**
     * @param  string  $field The model field
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $states The States enum class
     */
    public function __construct(string $field, Model $model, string $states)
    {
        $this->field = $field;

        $this->model = $model;

        $this->states = $states;
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
    public function was(States $state): bool
    {
        return $this->history()->to($state)->exists();
    }

    /**
     * @param  States  $state
     *
     * @return mixed
     */
    public function timesWas(States $state): int
    {
        return $this->history()->to($state)->count();
    }

    /**
     * @param  States  $state
     *
     * @return \Carbon\Carbon|null
     */
    public function whenWas(States $state): ?Carbon
    {
        $stateHistory = $this->snapshotWhen($state);

        if ($stateHistory === null) {
            return null;
        }

        return $stateHistory->created_at;
    }

    /**
     * @param  States  $state
     *
     * @return \byteit\LaravelExtendedStateMachines\Models\StateHistory|null
     */
    public function snapshotWhen(States $state): ?StateHistory
    {
        return $this->history()->to($state)->latest('id')->first();
    }

    /**
     * @param  States  $state
     *
     * @return \Illuminate\Support\Collection
     */
    public function snapshotsWhen(States $state): Collection
    {
        return $this->history()->to($state)->get();
    }

    /**
     * @param  States  $from
     * @param  States  $to
     *
     * @return bool
     */
    public function canBe(States $from, States $to): bool
    {
        return in_array($to, $from->transition(), true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function pendingTransitions(): MorphMany
    {
        return $this->model->pendingTransitions()->forField($this->field);
    }

    /**
     * @return bool
     */
    public function hasPendingTransitions(): bool
    {
        return $this->pendingTransitions()->notApplied()->exists();
    }

    /**
     * @param  States  $from
     * @param  States  $to
     * @param  array  $customProperties
     * @param  mixed|null  $responsible
     *
     * @throws \byteit\LaravelExtendedStateMachines\Exceptions\TransitionNotAllowedException
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

        $validator = $this->validatorForTransition($from, $to, $this->model);
        if ($validator !== null && $validator->fails()) {
            throw new ValidationException($validator);
        }

//        $beforeTransitionHooks = $this->beforeTransitionHooks()[$from] ?? [];
//
//        collect($beforeTransitionHooks)
//          ->each(function ($callable) use ($to) {
//              $callable($to, $this->model);
//          });

        $field = $this->field;
        $this->model->$field = $to;

        $changedAttributes = $this->model->getChangedAttributes();

        $this->model->save();

        if ($this->recordHistory()) {
            $responsible = $responsible ?? auth()->user();

            $this->model->recordState($field, $from, $to, $customProperties,
              $responsible, $changedAttributes);
        }

//        $afterTransitionHooks = $this->afterTransitionHooks()[$to] ?? [];

//        collect($afterTransitionHooks)
//          ->each(function ($callable) use ($from) {
//              $callable($from, $this->model);
//          });

        $this->cancelAllPendingTransitions();
    }

    /**
     * @param $from
     * @param $to
     * @param  Carbon  $when
     * @param  array  $customProperties
     * @param  null  $responsible
     *
     * @return null|PendingTransition
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
      $from,
      $to,
      Carbon $when,
      array $customProperties = [],
      $responsible = null
    ): ?PendingTransition {
        if ($to === $this->currentState()) {
            return null;
        }

        if ( ! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException();
        }

        $responsible = $responsible ?? auth()->user();

        return $this->model->recordPendingTransition(
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
    public function cancelAllPendingTransitions(): void
    {
        $this->pendingTransitions()->delete();
    }

    /**
     * @return array
     */
    public function transitions(): array
    {
        return collect($this->states::cases())
          ->map(fn(States $states) => $states->transition())
          ->all();
    }

    /**
     * @return States
     */
    public function defaultState(): States
    {
        try{
            $reflection = new ReflectionClass($this->states);
            /** @var \ReflectionAttribute[] $attributes */
            $attributes = $reflection->getAttributes(DefaultState::class);
        } catch (ReflectionException $e) {
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

        } catch (ReflectionException $e) {
            return false;
        }
        /** @var DefaultState[] $attributes */
        return count($attributes) === 1;
    }

    /**
     * @param $from
     * @param $to
     * @param $model
     *
     * @return \Illuminate\Contracts\Validation\Validator|null
     */
    public function validatorForTransition($from, $to, $model): ?Validator
    {
        return null;
    }

}
