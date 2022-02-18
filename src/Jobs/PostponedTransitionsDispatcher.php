<?php


namespace byteit\LaravelExtendedStateMachines\Jobs;


use byteit\LaravelExtendedStateMachines\Models\PostponedTransition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 */
class PostponedTransitionsDispatcher implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable, SerializesModels;

    public function handle(): void
    {
        PostponedTransition::with(['model'])
            ->notApplied()
            ->onScheduleOrOverdue()
            ->get()
            ->each(function (PostponedTransition $pendingTransition) {
                PostponedTransitionExecutor::dispatch($pendingTransition);

                $pendingTransition->applied_at = now();
                $pendingTransition->save();
            });
    }
}
