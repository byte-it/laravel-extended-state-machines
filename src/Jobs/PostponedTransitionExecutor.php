<?php


namespace byteit\LaravelExtendedStateMachines\Jobs;


use byteit\LaravelExtendedStateMachines\Exceptions\InvalidStartingStateException;
use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 */
class PostponedTransitionExecutor implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable, SerializesModels;

    public PostponedTransition $postponedTransition;

    public function __construct(PostponedTransition $postponedTransition)
    {
        $this->postponedTransition = $postponedTransition;
    }

    public function handle(): void
    {
        $field = $this->postponedTransition->field;
        $model = $this->postponedTransition->model;
        $from = $this->postponedTransition->from;
        $to = $this->postponedTransition->to;
        $customProperties = $this->postponedTransition->custom_properties;
        $responsible = $this->postponedTransition->responsible;

        if ($model->$field()->isNot($from)) {
            $exception = new InvalidStartingStateException(
                $from,
                $model->$field()->state()
            );

            $this->fail($exception);
            return;
        }

        $model->$field()->transitionTo($to, $customProperties, $responsible);
    }
}
