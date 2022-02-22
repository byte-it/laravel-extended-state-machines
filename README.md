# Laravel Enum State Machines
Define DFA like state machines for your eloquent models, keep track of changes and prevent unwanted flows of your business logic.

## Features/todos
- [x] Define state machines as native enum
- [x] Apply state machines to fields on eloquent models
- [x] Only allow transitions defined in the state machine
- [x] Allow additional properties to be applied to transitions
- [x] Postpone transitions 
- [x] Guards for transition
- [ ] Events
  - General events before/after each transition via named event
  - Events for each transition like eloquent models
- [x] Actions for defined transitions
- [ ] Action auto discovery + caching
- [ ] Gates to authorize transitions
- [ ] Pending/Running Transitions + lock any transition till the pending is performend or aborted
- [ ] Visualize state machines either via console or some html output including guards and actions
- [ ] Create common interface for past, pending and postponed transitions
- [ ] Add method to get all past/pending/postponed transitions
- [ ] Add loop handling to represent model updates
- [ ] Convert tests to pest
- [ ] Wrap transitions in transactions
 
## State machines
The state represents the current status of your model. (But model can have multiple states)
The state is defined as an enum, in which the cases represent the set of possible states.
```php
enum State: string {
  case Created = 'created';
  case Intermidiate = 'intermidiate';
  case Finished = 'finished';
}
```

The possible transitions are defined via the method `transitions` that uses the PHP `match` operation to define all possible 
outgoing transitions for a state.

```
enum State: string {
  case Created = 'created';
  case Intermidiate = 'intermidiate';
  case Finished = 'finished';
  
   public function transitions(): array
    {
        return match ($this) {
            self::Created => [self::Intermidiate],
            self::Intermidiate => [self::Finished],
            default => []
        };
    }
}
```

As default the first case of the enum will be used as initial/starting state. But it can be defined via the use of an attribute.


In difference to the theoretical definition of an DFA, we don't use final states. 


## Transitions
Transitions are not determined by input, but explicitly giving the state to transition to.

```php
  $model->status()->transitionTo(State::Intermidiate);
```


### Transition Bus/Pipeline System

A bus system similar to the event system.
Requirements:
- Handle gates
- Handle Action > Only one Action per Transition
  - Find a way to decide for sync or async execution
  - Allow sync/async dispatching like Jobs
- Dispatch events after transitioning
- Acquire and maintain locks

Split Actions and after events up. Gather possible actions. Find the absolut match.
Create an Action Construct like nova actions. 
An TransitionAction can have a handle or __invoke method. 



### Guards
It's not always enough to limit transitions by its start and end. For that it is possible
to add transition guards that can perform any additional validation. 

### Gates
Additionally, transitions can be limited by authorization gates. 

### Actions
Actions can be run before and after a transition is performed.
Be aware that a failed before transition cancels the whole transition.

Before Actions can modify the model or perform any other action. Changes to the model will be included in the `changed_attributes`
of the transition. (Except for actions that cause a pending transition)

The after action should be primary for things like notifications etc.



### Past transitions

### Pending transition
Some transitions carry out actions that take time, for example queued a job. During this time no further
transitions should be allowed to happen on that model. 
To achieve this, the transition must be marked as pending, save to the database and create some sort of lock,
for example via redis. 
Once all is done for the transition, it must be marked `finished`. That releases the lock and moves the transitions
from pending to past.

A transition will be marked as pending if one of the before actions is queued or it is marked by hand.

### Postponed transitions
Sometimes a transition shall be marked for the future, for example an expiration. The same result 
could be achieved by checking the expiration in a cron job and trigger the transition then.
A postponed transition has the advantage to see at once when this transition should happen.
The transition will be canceled once the state transitioned to a state which does not allow the postponed transition anymore.


### History
