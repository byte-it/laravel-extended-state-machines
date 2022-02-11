<?php

namespace byteit\LaravelExtendedStateMachines\Traits;

use byteit\LaravelExtendedStateMachines\Models\PendingTransition;
use byteit\LaravelExtendedStateMachines\Models\StateHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\StateMachines\State;
use byteit\LaravelExtendedStateMachines\StateMachines\StateMachine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Javoscript\MacroableModels\Facades\MacroableModels;


/**
 * Trait HasStateMachines
 *
 * @todo Create separat Contract
 *
 * @package byteit\LaravelExtendedStateMachines\Traits
 * @property array $stateMachines
 */
trait HasStateMachines
{

    /**
     * @return void
     */
    public static function bootHasStateMachines(): void
    {
        $model = new static();


        // @todo Maybe remove this in favor of dedicated methods on the model
        collect($model->stateMachines)
          ->each(function ($_, string $field) {
              MacroableModels::addMacro(
                static::class,
                $field,
                function () use ($field) {
                    $stateMachine = new StateMachine(
                      $field,
                      $this,
                      $this->stateMachines['field']
                    );
                    return new State(
                      $this->{$stateMachine->field},
                      $stateMachine
                    );
                });

              $camelField = Str::of($field)->camel();

              MacroableModels::addMacro(
                static::class,
                $camelField,
                function () use ($field) {
                    $stateMachine = new  StateMachine(
                      $field,
                      $this,
                      $this->stateMachines[$field]
                    );
                    return new State(
                      $this->{$stateMachine->field},
                      $stateMachine
                    );
                });

              $studlyField = Str::of($field)->studly();

              Builder::macro("whereHas{$studlyField}",
                function ($callable = null) use ($field) {
                    $model = $this->getModel();

                    if ( ! method_exists($model, 'stateHistory')) {
                        return $this->newQuery();
                    }

                    return $this->whereHas('stateHistory',
                      function ($query) use ($field, $callable) {
                          $query->forField($field);
                          if ($callable !== null) {
                              $callable($query);
                          }
                          return $query;
                      });
                });
          });


        self::creating(static function (Model|self $model) {
            $model->initStateMachines();
        });

        self::created(static function (Model|self $model) {
            collect($model->stateMachines)
              ->each(function ($_, $field) use ($model) {
                  $currentState = $model->$field;
                  $stateMachine = $model->$field()->stateMachine();

                  if ($currentState === null) {
                      return;
                  }

                  if ( ! $stateMachine->recordHistory()) {
                      return;
                  }

                  $responsible = auth()->user();

                  $changedAttributes = $model->getChangedAttributes();

                  $model->recordState($field, null, $currentState, [],
                    $responsible, $changedAttributes);
              });
        });
    }

    /**
     * Apply the enum casts for all statemachines
     *
     * @return void
     */
    public function initializeHasStateMachines(): void
    {
        $this->mergeCasts($this->stateMachines);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function initStateMachines(): void
    {
        collect($this->stateMachines)
          ->each(function ($statesClass, $field) {
              $stateMachine = new StateMachine($field, $this, $statesClass);

              $this->{$field} = $this->{$field} ?? $stateMachine->defaultState();
          });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function stateHistory(): MorphMany
    {
        return $this->morphMany(StateHistory::class, 'model');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function pendingTransitions(): MorphMany
    {
        return $this->morphMany(PendingTransition::class, 'model');
    }

    /**
     * @return array
     */
    public function getChangedAttributes(): array
    {
        return collect($this->getDirty())
          ->mapWithKeys(function ($_, $attribute) {
              return [
                $attribute => [
                  'new' => data_get($this->getAttributes(), $attribute),
                  'old' => data_get($this->getOriginal(), $attribute),
                ],
              ];
          })
          ->toArray();
    }

    /**
     * @param  string  $field
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States|null  $from
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $to
     * @param  array  $customProperties
     * @param  null  $responsible
     * @param  array  $changedAttributes
     *
     * @return \byteit\LaravelExtendedStateMachines\Models\StateHistory|bool
     */
    public function recordState(
      string $field,
      ?States $from,
      States $to,
      array $customProperties = [],
      $responsible = null,
      array $changedAttributes = []
    ): StateHistory|bool {
        $stateHistory = StateHistory::make([
          'field' => $field,
          'from' => $from,
          'to' => $to,
          'states' => $this->stateMachines[$field],
          'custom_properties' => $customProperties,
          'changed_attributes' => $changedAttributes,
        ]);

        if ($responsible !== null) {
            $stateHistory->responsible()->associate($responsible);
        }

        return $this->stateHistory()->save($stateHistory);
    }

    /**
     * @param  string  $field
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States|null  $from
     * @param  \byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States  $to
     * @param  \Illuminate\Support\Carbon  $when
     * @param  array  $customProperties
     * @param  mixed  $responsible
     *
     * @return \byteit\LaravelExtendedStateMachines\Models\PendingTransition|bool
     */
    public function recordPendingTransition(
      string $field,
      ?States $from,
      States $to,
      Carbon $when,
      array $customProperties = [],
      mixed $responsible = null
    ): PendingTransition|bool {
        /** @var PendingTransition $pendingTransition */
        $pendingTransition = PendingTransition::make([
          'field' => $field,
          'from' => $from,
          'to' => $to,
          'states' => $this->stateMachines[$field],
          'transition_at' => $when,
          'custom_properties' => $customProperties,
        ]);

        if ($responsible !== null) {
            $pendingTransition->responsible()->associate($responsible);
        }

        return $this->pendingTransitions()
          ->save($pendingTransition);
    }

}
