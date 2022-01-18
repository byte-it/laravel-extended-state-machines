<?php

namespace byteit\LaravelExtendedStateMachines\Traits;

use byteit\LaravelExtendedStateMachines\Models\PendingTransition;
use byteit\LaravelExtendedStateMachines\Models\StateHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\State;
use byteit\LaravelExtendedStateMachines\StateMachines\StateMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Javoscript\MacroableModels\Facades\MacroableModels;


/**
 * Trait HasStateMachines
 *
 * @todo Create separat Contract
 * @todo Add Enum casts
 *
 * @package byteit\LaravelExtendedStateMachines\Traits
 * @property array $stateMachines
 */
trait HasStateMachines
{

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

    public function initializeHasStateMachines(): void
    {
        $this->mergeCasts($this->stateMachines);
    }

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

    public function initStateMachines(): void
    {
        collect($this->stateMachines)
          ->each(function ($statesClass, $field) {
              $stateMachine = new StateMachine($field, $this, $statesClass);

              $this->{$field} = $this->{$field} ?? $stateMachine->defaultState();
          });
    }

    public function stateHistory(): MorphMany
    {
        return $this->morphMany(StateHistory::class, 'model');
    }

    public function pendingTransitions(): MorphMany
    {
        return $this->morphMany(PendingTransition::class, 'model');
    }

    public function recordState(
      $field,
      $from,
      $to,
      $customProperties = [],
      $responsible = null,
      $changedAttributes = []
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

    public function recordPendingTransition(
      $field,
      $from,
      $to,
      $when,
      $customProperties = [],
      $responsible = null
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
